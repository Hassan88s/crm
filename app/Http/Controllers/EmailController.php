<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\Speaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailController extends Controller
{
    public function index()
    {
        $speakers = Speaker::with('event')->orderBy('first_name')->get();
        $events   = \App\Models\Event::withCount('speakers')->orderBy('name')->get();
        $logs     = EmailLog::with('speaker')->latest()->take(20)->get();
        $sentTotal   = EmailLog::where('status', 'sent')->count();
        $failedTotal = EmailLog::where('status', 'failed')->count();

        return view('admin.emails.index', compact('speakers', 'events', 'logs', 'sentTotal', 'failedTotal'));
    }

    public function send(Request $request)
    {
        // Either send by event (all speakers of that event) or by manually selected speaker_ids
        $byEvent = $request->filled('event_id');

        $data = $request->validate(array_merge(
            [
                'subject'    => 'required|string|max:255',
                'body'       => 'required|string',
                'attachment' => 'nullable|file|max:20480',
            ],
            $byEvent
                ? ['event_id' => 'required|exists:events,id']
                : ['speaker_ids' => 'required|array|min:1', 'speaker_ids.*' => 'exists:speakers,id']
        ));

        // Load SMTP config fresh from .env
        $this->applySmtpConfig();

        $speakers = $byEvent
            ? Speaker::where('event_id', $data['event_id'])->get()
            : Speaker::whereIn('id', $data['speaker_ids'])->get();

        if ($speakers->isEmpty()) {
            return back()->with('send_result', '⚠️ No speakers found for the selected event.');
        }

        // Handle optional file attachment
        $attachPath     = null;
        $attachOrigName = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file           = $request->file('attachment');
            $attachOrigName = $file->getClientOriginalName();
            $safeName       = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $attachOrigName);

            // Ensure directory exists
            $dir = storage_path('app' . DIRECTORY_SEPARATOR . 'email_attachments');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            // Move file directly to avoid storeAs issues
            $attachPath = $dir . DIRECTORY_SEPARATOR . $safeName;
            $file->move($dir, $safeName);

            \Log::info('Email attachment stored', [
                'path'   => $attachPath,
                'exists' => file_exists($attachPath),
                'size'   => file_exists($attachPath) ? filesize($attachPath) : 0,
                'orig'   => $attachOrigName,
            ]);
        }

        $sent   = 0;
        $failed = 0;

        foreach ($speakers as $speaker) {
            // Replace variables in subject and body
            $subject = $this->replaceVars($data['subject'], $speaker);
            $body    = $this->replaceVars($data['body'], $speaker);

            try {
                $fromAddress = config('mail.from.address');
                $fromName    = config('mail.from.name');

                Mail::html($body, function ($msg) use ($speaker, $subject, $fromAddress, $fromName, $attachPath, $attachOrigName) {
                    $msg->to($speaker->email, $speaker->full_name)
                        ->subject($subject)
                        ->from($fromAddress, $fromName);
                    if ($attachPath) {
                        if (file_exists($attachPath)) {
                            $msg->attach($attachPath, ['as' => $attachOrigName]);
                            \Log::info('Attachment added to email', ['to' => $speaker->email, 'file' => $attachOrigName]);
                        } else {
                            \Log::warning('Attachment file missing', ['path' => $attachPath]);
                        }
                    }
                });

                EmailLog::create([
                    'speaker_id' => $speaker->id,
                    'to_email'   => $speaker->email,
                    'to_name'    => $speaker->full_name,
                    'subject'    => $subject,
                    'body'       => $body,
                    'status'     => 'sent',
                ]);

                $sent++;
            } catch (\Exception $e) {
                EmailLog::create([
                    'speaker_id' => $speaker->id,
                    'to_email'   => $speaker->email,
                    'to_name'    => $speaker->full_name,
                    'subject'    => $subject,
                    'body'       => $body,
                    'status'     => 'failed',
                    'error'      => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        // Clean up temp attachment file
        if ($attachPath && file_exists($attachPath)) {
            @unlink($attachPath);
        }

        $msg = "✅ {$sent} email(s) sent successfully.";
        if ($failed) $msg .= " ❌ {$failed} failed.";
        if ($attachOrigName) $msg .= " 📎 Attachment: {$attachOrigName}";

        return back()->with('send_result', $msg)->with('send_sent', $sent)->with('send_failed', $failed);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function replaceVars(string $text, Speaker $speaker): string
    {
        return str_replace(
            ['{first_name}', '{last_name}', '{name}', '{full_name}',
             '{email}', '{company}', '{title}', '{seniority}', '{country}'],
            [$speaker->first_name, $speaker->last_name, $speaker->first_name, $speaker->full_name,
             $speaker->email, $speaker->company ?? '', $speaker->title ?? '',
             $speaker->seniority ?? '', $speaker->country ?? ''],
            $text
        );
    }

    private function applySmtpConfig(): void
    {
        $settingsController = new SettingsController();
        $env = $settingsController->readEnvValues([
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME',
            'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        ]);

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $env['MAIL_HOST']         ?? '',
            'mail.mailers.smtp.port'       => $env['MAIL_PORT']         ?? 587,
            'mail.mailers.smtp.username'   => $env['MAIL_USERNAME']     ?? '',
            'mail.mailers.smtp.password'   => $env['MAIL_PASSWORD']     ?? '',
            'mail.mailers.smtp.encryption' => $env['MAIL_ENCRYPTION']   ?? 'tls',
            'mail.from.address'            => $env['MAIL_FROM_ADDRESS'] ?? '',
            'mail.from.name'               => $env['MAIL_FROM_NAME']    ?? '',
        ]);

        // Purge cached mailer so Laravel uses the fresh config
        Mail::purge('smtp');
    }

    public function aiDraft(Request $request)
    {
        $data = $request->validate([
            'speaker_id'     => 'required|exists:speakers,id',
            'topic'          => 'nullable|string|max:300',
            'attachment'     => 'nullable|string|max:200',
            'event_name'     => 'nullable|string|max:200',
            'event_date'     => 'nullable|string|max:100',
            'event_location' => 'nullable|string|max:200',
        ]);

        $env    = (new \App\Http\Controllers\SettingsController)->readEnvValues(['OPENAI_API_KEY', 'APP_NAME']);
        $apiKey = $env['OPENAI_API_KEY'] ?? '';
        if (!$apiKey) {
            return response()->json(['error' => 'OpenAI API key not configured. Go to Settings → AI.'], 422);
        }

        $speaker    = Speaker::findOrFail($data['speaker_id']);
        $appName    = $env['APP_NAME'] ?? config('app.name', 'PulseCore');
        $topic      = $data['topic']      ?? '';
        $attachment = $data['attachment'] ?? '';
        $eventName  = $data['event_name'] ?? '';
        $eventDate  = $data['event_date'] ?? '';
        $eventLoc   = $data['event_location'] ?? '';

        // Build speaker profile string
        $speakerProfile = implode("\n", array_filter([
            "Name: {$speaker->full_name}",
            $speaker->title    ? "Title: {$speaker->title}"       : null,
            $speaker->company  ? "Company: {$speaker->company}"   : null,
            $speaker->seniority? "Seniority: {$speaker->seniority}" : null,
            $speaker->country  ? "Country: {$speaker->country}"   : null,
            $speaker->email    ? "Email: {$speaker->email}"       : null,
        ]));

        $eventContext = $eventName
            ? "Event: {$eventName}" . ($eventDate ? ", {$eventDate}" : '') . ($eventLoc ? ", {$eventLoc}" : '')
            : '';

        $topicLine      = $topic      ? "Requested topic: {$topic}"                        : 'No specific topic given — suggest the best fit based on their expertise.';
        $attachmentLine = $attachment ? "Mention that we are attaching: {$attachment}"     : '';
        $topicContext   = $topic      ? ", and the requested topic: \"{$topic}\""          : '';

        $prompt = "You are an expert event coordinator writing a professional speaker invitation email.\n\n"
            . "SPEAKER PROFILE (from our CRM):\n{$speakerProfile}\n\n"
            . "TASK:\n"
            . "1. Use your knowledge about {$speaker->full_name} to research who they are — their background, expertise, notable work, publications, or known opinions.\n"
            . "2. Based on their expertise{$topicContext}, suggest the most fitting speaking topic for them.\n"
            . "3. Write a highly personalised, professional invitation email from {$appName}.\n\n"
            . "CONTEXT:\n"
            . ($eventContext ? "{$eventContext}\n" : "No specific event provided.\n")
            . "{$topicLine}\n"
            . ($attachmentLine ? "{$attachmentLine}\n" : '')
            . "\nEMAIL REQUIREMENTS:\n"
            . "- Warm, professional, personalised tone — reference specific things you know about them\n"
            . "- Mention their specific expertise and why THEY specifically are ideal for this event\n"
            . "- Include the suggested speaking topic (with a compelling title if no topic given)\n"
            . "- Keep it concise — under 250 words\n"
            . "- End with a clear call to action (reply to confirm interest)\n"
            . "- NO placeholders like [NAME] — use their actual name\n\n"
            . "OUTPUT FORMAT (JSON only, no markdown):\n"
            . "{\n"
            . "  \"subject\": \"the email subject line\",\n"
            . "  \"suggested_topic\": \"the topic title you are suggesting\",\n"
            . "  \"body_html\": \"full email body as HTML (use <p> tags, no inline styles)\",\n"
            . "  \"body_plain\": \"same email as plain text for preview\"\n"
            . "}";

        $payload = json_encode([
            'model'       => 'gpt-4o-mini',
            'messages'    => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.7,
            'max_tokens'  => 1200,
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
            ],
        ]);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) return response()->json(['error' => 'Connection error: ' . $curlErr], 422);

        $result = json_decode($raw, true);
        if ($httpCode !== 200) {
            return response()->json(['error' => 'OpenAI: ' . ($result['error']['message'] ?? "HTTP {$httpCode}")], 422);
        }

        $content = $result['choices'][0]['message']['content'] ?? '';
        // Strip markdown fences
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/\s*```$/m', '', $content);

        $draft = json_decode(trim($content), true);
        if (!$draft || empty($draft['subject'])) {
            return response()->json(['error' => 'AI returned unexpected format. Try again.'], 422);
        }

        return response()->json([
            'subject'         => $draft['subject'],
            'body'            => $draft['body_html'],
            'body_plain'      => $draft['body_plain'],
            'suggested_topic' => $draft['suggested_topic'] ?? '',
        ]);
    }
}
