<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailReply;
use App\Models\EmailLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class ReplyController extends Controller
{
    public function index(Request $request)
    {
        // Sync "No Reply" entries before showing the list
        $this->syncNoReplyEntries();

        $category = $request->get('category', 'all');
        $categories = ['Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Manual Review','No Reply'];

        $query = EmailReply::with('speaker')->orderByDesc('received_at');
        if ($category !== 'all' && in_array($category, $categories)) {
            $query->where('category', $category);
        }

        $replies = $query->paginate(30)->withQueryString();

        // Counts per category
        $counts = EmailReply::selectRaw('category, count(*) as total')
            ->groupBy('category')->pluck('total', 'category')->toArray();
        $counts['all'] = array_sum($counts);

        $lastFetch = EmailReply::max('classified_at');

        return view('admin.replies.index', compact('replies','category','categories','counts','lastFetch'));
    }

    public function show(EmailReply $reply)
    {
        $reply->load('speaker');
        return view('admin.replies.show', compact('reply'));
    }

    public function fetch()
    {
        try {
            Artisan::call('emails:classify-replies', ['--limit' => 100]);
            $output = Artisan::output();
            // Count how many were classified (look for "Classified: N" in output)
            preg_match('/Classified:\s*(\d+)/', $output, $m);
            $count = (int)($m[1] ?? 0);
            // Sync "No Reply" entries after classification
            $this->syncNoReplyEntries();
            return response()->json(['ok' => true, 'classified' => $count, 'output' => $output]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function reclassify(EmailReply $reply)
    {
        // Re-run classification on existing stored body
        $apiKey = env('OPENAI_API_KEY', '');
        if (!$apiKey) {
            return response()->json(['error' => 'No OpenAI key configured.'], 422);
        }

        $cmd = new \App\Console\Commands\ClassifyEmailReplies();
        // Use reflection to call private classify method
        $ref = new \ReflectionClass($cmd);
        $method = $ref->getMethod('classify');
        $method->setAccessible(true);
        [$category, $score, $raw] = $method->invoke($cmd, $reply->body_plain, $reply->subject ?? '', $apiKey);

        $reply->update([
            'category'      => $category,
            'ai_score'      => $score,
            'ai_raw'        => $raw,
            'classified_at' => now(),
        ]);

        return response()->json(['ok' => true, 'category' => $category, 'score' => $score]);
    }

    // ── Sync "No Reply" entries ─────────────────────────────────────────────

    private function syncNoReplyEntries(): void
    {
        // Get all unique emails we've sent to (successfully)
        $sentEmails = EmailLog::where('status', 'sent')
            ->select('to_email', 'to_name', 'speaker_id', 'subject')
            ->selectRaw('MAX(created_at) as last_sent_at')
            ->groupBy('to_email', 'to_name', 'speaker_id', 'subject')
            ->get()
            ->unique('to_email');

        // Get emails that have a real reply (any category except "No Reply")
        $repliedEmails = EmailReply::where('category', '!=', 'No Reply')
            ->pluck('from_email')
            ->map(fn($e) => strtolower($e))
            ->toArray();

        // Get emails that already have a "No Reply" entry
        $existingNoReply = EmailReply::where('category', 'No Reply')
            ->pluck('from_email')
            ->map(fn($e) => strtolower($e))
            ->toArray();

        foreach ($sentEmails as $log) {
            $email = strtolower($log->to_email);

            // If they replied, remove any "No Reply" entry
            if (in_array($email, $repliedEmails)) {
                EmailReply::where('from_email', $email)->where('category', 'No Reply')->delete();
                continue;
            }

            // If already has a "No Reply" entry, skip
            if (in_array($email, $existingNoReply)) {
                continue;
            }

            // Create a "No Reply" entry
            EmailReply::create([
                'message_id'    => 'no-reply-' . md5($email),
                'speaker_id'    => $log->speaker_id,
                'from_email'    => $log->to_email,
                'from_name'     => $log->to_name,
                'subject'       => 'No reply to: ' . ($log->subject ?? '(no subject)'),
                'body_plain'    => 'This speaker was emailed but has not replied yet.',
                'received_at'   => $log->last_sent_at ?? now(),
                'category'      => 'No Reply',
                'ai_score'      => null,
                'ai_raw'        => null,
                'classified_at' => now(),
            ]);
        }
    }

    // ── Send reply to an email ──────────────────────────────────────────────

    public function sendReply(Request $request, EmailReply $reply)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        // Apply SMTP config (reuse EmailController logic)
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

        $toEmail     = $reply->from_email;
        $toName      = $reply->from_name ?? $reply->from_email;
        $subject     = $data['subject'];
        $body        = $data['body'];
        $fromAddress = config('mail.from.address');
        $fromName    = config('mail.from.name');

        try {
            Mail::html($body, function ($msg) use ($toEmail, $toName, $subject, $fromAddress, $fromName) {
                $msg->to($toEmail, $toName)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
            });

            // Log in email_logs
            EmailLog::create([
                'speaker_id' => $reply->speaker_id,
                'to_email'   => $toEmail,
                'to_name'    => $toName,
                'subject'    => $subject,
                'body'       => $body,
                'status'     => 'sent',
            ]);

            return back()->with('reply_success', 'Reply sent successfully to ' . $toEmail);
        } catch (\Exception $e) {
            EmailLog::create([
                'speaker_id' => $reply->speaker_id,
                'to_email'   => $toEmail,
                'to_name'    => $toName,
                'subject'    => $subject,
                'body'       => $body,
                'status'     => 'failed',
                'error'      => $e->getMessage(),
            ]);

            return back()->with('reply_error', 'Failed to send reply: ' . $e->getMessage());
        }
    }
}
