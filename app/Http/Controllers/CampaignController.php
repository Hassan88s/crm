<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailLog;
use App\Models\Event;
use App\Models\Speaker;
use App\Services\CampaignSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    public const DEFAULT_BODY_TEMPLATE = <<<'HTML'
<p>Dear {first_name},</p>

<p>I hope this message finds you well.</p>

<p>We are currently producing the <strong>7th Annual Excellence in Digital Banking International Summit</strong>, taking place on <strong>25–27th November in Amsterdam</strong>.</p>

<p>After a careful review of your work and thought leadership, it would be an honour to welcome you as one of our distinguished speakers at this year's forum.</p>

<p>Based on our research, we believe your insights would perfectly complement the topic: <strong>"{topic}"</strong>.</p>

<p>Please find a draft program attached for your reference. Of course, please feel very welcome to suggest an alternative topic that best represents your expertise and current focus.</p>

<p>This year's summit will bring together over 400 senior banking professionals and digital transformation leaders from across the globe — creating an exclusive platform for exchange, inspiration, and forward-thinking dialogue.</p>

<p>Should you wish, I'd be delighted to share the agenda from previous editions to give you a clearer idea of the event's scope and calibre.</p>

<p>I would truly appreciate your feedback by <strong>{deadline_date}</strong>, so we can secure your spot or, alternatively, move forward with other speakers in case your schedule does not allow participation.</p>

<p>Looking forward to hearing from you soon.</p>

<p>Warm regards,</p>
HTML;

    public const DEFAULT_SUBJECT_TEMPLATE = "Speaker invitation — {event_name}";

    public function index()
    {
        $campaigns = Campaign::withCount('recipients')->latest()->get();
        $sentTotal   = EmailLog::where('status', 'sent')->count();
        $failedTotal = EmailLog::where('status', 'failed')->count();
        return view('admin.campaigns.index', compact('campaigns', 'sentTotal', 'failedTotal'));
    }

    public function create(Request $request)
    {
        $events   = Event::withCount('speakers')->orderBy('name')->get();
        $speakers = Speaker::with('event')->orderBy('first_name')->get();
        return view('admin.campaigns.create', [
            'events'           => $events,
            'speakers'         => $speakers,
            'defaultSubject'   => self::DEFAULT_SUBJECT_TEMPLATE,
            'defaultBody'      => self::DEFAULT_BODY_TEMPLATE,
            'preselectedEventId' => $request->get('event_id'),
        ]);
    }

    public function store(Request $request)
    {
        $byEvent = $request->filled('event_id');
        $rules = [
            'name'             => 'required|string|max:120',
            'subject_template' => 'required|string|max:255',
            'body_template'    => 'required|string',
            'agenda_pdf'       => 'nullable|file|mimes:pdf|max:20480',
            'attach_agenda'    => 'nullable|in:0,1',
            'throttle_seconds' => 'required|integer|min:30|max:3600',
            'event_id'         => 'nullable|exists:events,id',
            'start_now'        => 'nullable|in:0,1',
        ];
        $rules = array_merge($rules, $byEvent
            ? []
            : ['speaker_ids' => 'required|array|min:1', 'speaker_ids.*' => 'exists:speakers,id']);

        $data = $request->validate($rules);

        $eventId = $byEvent ? (int) $data['event_id'] : null;

        // Resolve recipients
        $speakerIds = $byEvent
            ? Speaker::where('event_id', $eventId)->pluck('id')->all()
            : $data['speaker_ids'];

        if (empty($speakerIds)) {
            return back()->withInput()->with('error', 'No speakers selected.');
        }

        // Save the PDF locally
        $pdfPath = null;
        $pdfFilename = null;
        $openaiFileId = null;
        if ($request->hasFile('agenda_pdf')) {
            $f = $request->file('agenda_pdf');
            $pdfFilename = $f->getClientOriginalName();
            $safe = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $pdfFilename);
            $dir = storage_path('app' . DIRECTORY_SEPARATOR . 'campaign_pdfs');
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $f->move($dir, $safe);
            $pdfPath = 'campaign_pdfs/' . $safe;

            // Upload to OpenAI Files API
            try {
                $openaiFileId = $this->uploadPdfToOpenAI($dir . DIRECTORY_SEPARATOR . $safe, $pdfFilename);
            } catch (\Throwable $e) {
                // Continue without file_search — log only
                \Log::warning('Failed to upload PDF to OpenAI Files', ['error' => $e->getMessage()]);
            }
        }

        $campaign = Campaign::create([
            'name'             => $data['name'],
            'subject_template' => $data['subject_template'],
            'body_template'    => $data['body_template'],
            'agenda_pdf_path'  => $pdfPath,
            'agenda_filename'  => $pdfFilename,
            'openai_file_id'   => $openaiFileId,
            'event_id'         => $eventId,
            'throttle_seconds' => (int) $data['throttle_seconds'],
            'attach_agenda'    => $request->boolean('attach_agenda', true),
            'status'           => 'draft',
            'total_count'      => count($speakerIds),
        ]);

        // Create recipient rows
        foreach ($speakerIds as $sid) {
            CampaignRecipient::firstOrCreate(
                ['campaign_id' => $campaign->id, 'speaker_id' => $sid],
                ['status' => 'pending']
            );
        }

        // If user clicked "Save & Start"
        if ($request->boolean('start_now')) {
            $this->startCampaign($campaign);
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('success', 'Campaign started. The first email will go out within ~1 minute.');
        }

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('success', 'Campaign saved as draft.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load(['recipients.speaker', 'recipients.smtpAccount', 'event']);
        return view('admin.campaigns.show', compact('campaign'));
    }

    public function start(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'paused'])) {
            return back()->with('error', 'Campaign cannot be started from its current state.');
        }
        $this->startCampaign($campaign);
        return back()->with('success', 'Campaign started.');
    }

    public function pause(Campaign $campaign)
    {
        if ($campaign->status !== 'running') {
            return back()->with('error', 'Only running campaigns can be paused.');
        }
        $campaign->update(['status' => 'paused']);
        return back()->with('success', 'Campaign paused.');
    }

    public function resume(Campaign $campaign)
    {
        if ($campaign->status !== 'paused') {
            return back()->with('error', 'Only paused campaigns can be resumed.');
        }
        $this->startCampaign($campaign, fromResume: true);
        return back()->with('success', 'Campaign resumed.');
    }

    public function destroy(Campaign $campaign)
    {
        // Best-effort: delete OpenAI file
        if ($campaign->openai_file_id) {
            $this->deleteOpenAIFile($campaign->openai_file_id);
        }
        // Delete local PDF
        if ($campaign->agenda_pdf_path) {
            $localPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $campaign->agenda_pdf_path));
            if (file_exists($localPath)) @unlink($localPath);
        }

        $campaign->delete();
        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    public function previewRecipient(Request $request, Campaign $campaign, Speaker $speaker)
    {
        try {
            $sender = app(CampaignSender::class);
            $ai = $sender->generateEmail($campaign, $speaker);
            $subject = $sender->replaceVars($ai['subject'], $speaker, $ai['topic'], $campaign);
            $bodyHtml = $sender->replaceVars($ai['body_html'], $speaker, $ai['topic'], $campaign);

            return response()->json([
                'ok'        => true,
                'topic'     => $ai['topic'],
                'subject'   => $subject,
                'body_html' => $bodyHtml,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function startCampaign(Campaign $campaign, bool $fromResume = false): void
    {
        $throttle = max(30, (int) $campaign->throttle_seconds);

        // Re-stamp scheduled_at for all pending recipients from now
        $pending = $campaign->recipients()->where('status', 'pending')->orderBy('id')->get();
        $now = now();
        foreach ($pending as $i => $r) {
            $r->update(['scheduled_at' => $now->copy()->addSeconds($i * $throttle)]);
        }

        $update = ['status' => 'running'];
        if (!$fromResume && !$campaign->started_at) {
            $update['started_at'] = $now;
        }
        $campaign->update($update);
    }

    private function uploadPdfToOpenAI(string $absolutePath, string $originalName): ?string
    {
        $apiKey = (new SettingsController)->readEnvValues(['OPENAI_API_KEY'])['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');
        if (!$apiKey) return null;

        $cfile = new \CURLFile($absolutePath, 'application/pdf', $originalName);

        $ch = curl_init('https://api.openai.com/v1/files');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'purpose' => 'assistants',
                'file'    => $cfile,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($raw, true);
        if (isset($json['error'])) {
            throw new \RuntimeException($json['error']['message'] ?? 'OpenAI files upload failed');
        }
        return $json['id'] ?? null;
    }

    private function deleteOpenAIFile(string $fileId): void
    {
        $apiKey = (new SettingsController)->readEnvValues(['OPENAI_API_KEY'])['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');
        if (!$apiKey) return;

        $ch = curl_init('https://api.openai.com/v1/files/' . $fileId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey],
        ]);
        @curl_exec($ch);
        curl_close($ch);
    }
}
