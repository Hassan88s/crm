<?php

namespace App\Services;

use App\Http\Controllers\SettingsController;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\EmailLog;
use App\Models\SmtpAccount;
use App\Models\Speaker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CampaignSender
{
    /**
     * Process one recipient end-to-end: AI draft, send, log.
     * Returns true on success, false on failure (failure is also persisted on the recipient).
     */
    public function processRecipient(CampaignRecipient $recipient): bool
    {
        $recipient->loadMissing(['campaign', 'speaker']);
        $campaign = $recipient->campaign;
        $speaker  = $recipient->speaker;

        if (!$campaign || !$speaker) {
            $recipient->update(['status' => 'failed', 'error' => 'Missing campaign or speaker']);
            return false;
        }

        if (empty($speaker->email)) {
            $recipient->update(['status' => 'skipped', 'error' => 'Speaker has no email address']);
            return false;
        }

        $recipient->update(['status' => 'processing']);

        try {
            // 1) Ask AI for the personalised email
            $ai = $this->generateEmail($campaign, $speaker);

            $subject = $this->replaceVars(
                $ai['subject'] ?? $campaign->subject_template,
                $speaker,
                $ai['topic'] ?? '',
                $campaign
            );
            $bodyHtml = $this->replaceVars(
                $ai['body_html'] ?? $campaign->body_template,
                $speaker,
                $ai['topic'] ?? '',
                $campaign
            );

            // 2) Pick rotated SMTP account (fallback to .env if none)
            $smtp = SmtpAccount::nextForRotation();
            if ($smtp) {
                $this->applySmtpConfigFromAccount($smtp);
            } else {
                $this->applyEnvSmtpConfig();
            }

            // 3) Send
            $fromAddress = config('mail.from.address');
            $fromName    = config('mail.from.name');

            // Append signature using the rotated SMTP's from_name
            $bodyHtml = $this->appendSignature($bodyHtml, $campaign, $fromName, $fromAddress);

            $attachPath  = $campaign->attach_agenda && $campaign->agenda_pdf_path
                ? storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $campaign->agenda_pdf_path))
                : null;
            $attachName  = $campaign->agenda_filename;

            Mail::html($bodyHtml, function ($msg) use ($speaker, $subject, $fromAddress, $fromName, $attachPath, $attachName) {
                $msg->to($speaker->email, $speaker->full_name)
                    ->subject($subject)
                    ->from($fromAddress, $fromName);
                if ($attachPath && file_exists($attachPath)) {
                    $msg->attach($attachPath, ['as' => $attachName ?: basename($attachPath)]);
                }
            });

            // 4) Log + update recipient + update campaign counters
            EmailLog::create([
                'speaker_id'      => $speaker->id,
                'smtp_account_id' => $smtp?->id,
                'to_email'        => $speaker->email,
                'to_name'         => $speaker->full_name,
                'subject'         => $subject,
                'body'            => $bodyHtml,
                'status'          => 'sent',
            ]);

            $recipient->update([
                'status'            => 'sent',
                'ai_topic'          => $ai['topic'] ?? null,
                'generated_subject' => $subject,
                'generated_body'    => $bodyHtml,
                'smtp_account_id'   => $smtp?->id,
                'sent_at'           => now(),
                'error'             => null,
            ]);

            $campaign->increment('sent_count');
            return true;
        } catch (\Throwable $e) {
            Log::error('Campaign send failed', [
                'recipient_id' => $recipient->id,
                'speaker_id'   => $speaker->id,
                'error'        => $e->getMessage(),
            ]);

            EmailLog::create([
                'speaker_id'      => $speaker->id,
                'smtp_account_id' => null,
                'to_email'        => $speaker->email,
                'to_name'         => $speaker->full_name,
                'subject'         => $recipient->generated_subject ?: ($campaign->subject_template ?? ''),
                'body'            => $recipient->generated_body ?: '',
                'status'          => 'failed',
                'error'           => $e->getMessage(),
            ]);

            $recipient->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            $campaign->increment('failed_count');
            return false;
        }
    }

    /**
     * Run AI generation only (used for previews and during send).
     * Returns ['topic' => string, 'subject' => string, 'body_html' => string].
     */
    public function generateEmail(Campaign $campaign, Speaker $speaker): array
    {
        $apiKey = (new SettingsController)->readEnvValues(['OPENAI_API_KEY'])['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');
        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY not configured.');
        }

        $deadline = now()->addDays(3)->translatedFormat('l, F j, Y');
        $eventBlock = $campaign->event
            ? "Event: {$campaign->event->name}\nLocation: {$campaign->event->location}\nDate: {$campaign->event->date?->format('M j, Y')}" . ($campaign->event->end_date ? " — {$campaign->event->end_date->format('M j, Y')}" : '')
            : "Event: (see attached agenda)";

        $profile = sprintf(
            "Name: %s\nTitle: %s\nCompany: %s\nSeniority: %s\nCountry: %s\nEmail: %s\nLinkedIn: %s",
            $speaker->full_name,
            $speaker->title ?: 'Unknown',
            $speaker->company ?: 'Unknown',
            $speaker->seniority ?: 'Unknown',
            $speaker->country ?: 'Unknown',
            $speaker->email,
            $speaker->linkedin_url ?: '(not provided)'
        );

        $userTemplate = $campaign->body_template;
        $subjectTpl   = $campaign->subject_template;

        $prompt = <<<PROMPT
You are crafting a UNIQUE, deeply personalised speaker invitation email.
This must NOT be a fill-in-the-blanks of the template — every speaker
gets a freshly written body that demonstrates real research about them.

EVENT CONTEXT:
{$eventBlock}
RSVP deadline: {$deadline}

SPEAKER PROFILE (starting facts only — research deeper online):
{$profile}

═══════════════════════════════════════════════════════════
PHASE 1 — READ THE AGENDA (attached PDF)
- Extract every session title, panel, keynote, theme, and track from the PDF.
- Note the topics, speakers' style, and any sub-topics.

PHASE 2 — DEEP RESEARCH ON THE SPEAKER (web_search)
Run multiple searches to learn about THIS person specifically:
  • "{$speaker->full_name}" + "{$speaker->company}" + role
  • "{$speaker->full_name}" recent talks / panels / podcasts / interviews
  • "{$speaker->full_name}" articles / publications / LinkedIn posts
  • Any signature topic, framework, or POV they're known for
  • Their company's recent strategic moves (digital banking, AI, payments, etc.)
You MUST run at least 2–3 different searches before writing.

PHASE 3 — PICK THE BEST AGENDA TOPIC
From the agenda, pick the SINGLE session/topic that best matches what you
learned about this speaker. Justify your choice mentally; don't include
the justification in the email — just use it to write authentically.

PHASE 4 — WRITE A UNIQUE EMAIL
Use the user's template (below) ONLY as a structural and tonal reference
— same length, same overall flow (intro → invitation → topic suggestion →
event scale → RSVP deadline → close), same warmth and professionalism.

But the actual sentences must be REWRITTEN for this speaker:
  • Reference 1–2 SPECIFIC things you found about them (a recent talk, an
    article, a known POV, a company achievement). No generic platitudes
    like "your impressive work".
  • Tie those specifics smoothly into why the chosen agenda topic suits them.
  • Vary sentence structure, opening line, and word choice from the template.
  • Keep the RSVP deadline ({$deadline}) and event scale (400+ banking pros)
    facts intact.
  • Output as clean HTML (<p>, <strong>) — no inline styles.
  • Length: similar to the template (5–7 short paragraphs).
  • Do NOT make up facts. If web_search returns nothing concrete, fall back
    to gently referencing their role and company sector — but rephrase
    every sentence in your own words; do NOT copy the template verbatim.

PHASE 5 — SUBJECT LINE
Write a personalised subject line. It should NOT be the literal
template — make it specific to the speaker and the topic. Keep it
under 80 characters.

═══════════════════════════════════════════════════════════
USER TEMPLATE (style/structure reference ONLY — rewrite, don't copy):

Subject reference: {$subjectTpl}

Body reference (HTML):
{$userTemplate}

═══════════════════════════════════════════════════════════
OUTPUT — return ONLY valid JSON, no markdown fences, no commentary:
{
  "topic": "<chosen agenda session/topic title>",
  "research_notes": "<1–2 sentence summary of what you found about the speaker, used internally>",
  "subject": "<personalised subject line>",
  "body_html": "<freshly written HTML email body for this speaker>"
}
PROMPT;

        // Build input — if an agenda PDF was uploaded, include it inline as an input_file
        // content block so the model can read it. Otherwise pass the prompt as a plain string.
        if ($campaign->openai_file_id) {
            $input = [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $prompt],
                    ['type' => 'input_file', 'file_id' => $campaign->openai_file_id],
                ],
            ]];
        } else {
            $input = $prompt;
        }

        $payload = [
            'model'             => 'gpt-5.4',
            'input'             => $input,
            'instructions'      => 'You write fully bespoke speaker invitations. Read the attached agenda PDF, run multiple web searches to learn about the speaker, pick the best matching agenda topic, then write a unique email body that references real specifics about the speaker. Use the user template only as a structural/tonal guide — REWRITE the sentences. Never copy the template verbatim. Return JSON only.',
            'tools'             => [
                ['type' => 'web_search_preview', 'search_context_size' => 'high'],
            ],
            'max_output_tokens' => 2200,
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$raw) {
            throw new \RuntimeException('OpenAI request failed: ' . $err);
        }

        $json = json_decode($raw, true);
        if (isset($json['error'])) {
            throw new \RuntimeException('OpenAI error: ' . ($json['error']['message'] ?? 'unknown'));
        }

        // Extract output text from Responses API shape
        $content = '';
        foreach ($json['output'] ?? [] as $item) {
            if (($item['type'] ?? '') === 'message' && isset($item['content'])) {
                foreach ($item['content'] as $block) {
                    if (($block['type'] ?? '') === 'output_text') {
                        $content = $block['text'] ?? '';
                        break 2;
                    }
                }
            }
        }

        $content = trim(preg_replace('/```json?|```/', '', $content));
        $result  = json_decode($content, true);

        if (!is_array($result) || !isset($result['body_html'])) {
            Log::warning('Campaign AI returned invalid JSON; falling back to template', [
                'speaker_id' => $speaker->id,
                'raw_excerpt' => mb_substr($content, 0, 500),
            ]);
            // Fallback: use the raw template
            return [
                'topic'           => 'a topic from the agenda',
                'subject'         => $subjectTpl,
                'body_html'       => $userTemplate,
                'research_notes'  => '',
            ];
        }

        return [
            'topic'           => trim($result['topic']     ?? ''),
            'subject'         => trim($result['subject']   ?? $subjectTpl),
            'body_html'       => $result['body_html'],
            'research_notes'  => trim($result['research_notes'] ?? ''),
        ];
    }

    public function replaceVars(string $text, Speaker $speaker, string $topic = '', ?Campaign $campaign = null): string
    {
        $deadline = now()->addDays(3)->translatedFormat('l, F j, Y');
        $eventName = $campaign?->event?->name ?? '';
        $eventLocation = $campaign?->event?->location ?? '';
        $eventDate = $campaign?->event?->date?->format('M j, Y') ?? '';

        return str_replace(
            ['{first_name}', '{last_name}', '{name}', '{full_name}', '{email}', '{company}', '{title}', '{seniority}', '{country}', '{topic}', '{deadline_date}', '{event_name}', '{event_location}', '{event_date}'],
            [
                $speaker->first_name,
                $speaker->last_name,
                $speaker->first_name,
                $speaker->full_name,
                $speaker->email,
                $speaker->company ?? '',
                $speaker->title ?? '',
                $speaker->seniority ?? '',
                $speaker->country ?? '',
                $topic,
                $deadline,
                $eventName,
                $eventLocation,
                $eventDate,
            ],
            $text
        );
    }

    /**
     * Append the campaign's signature to a body, replacing {smtp_from_name}
     * and {smtp_from_address} with the rotated SMTP account's from values.
     * If the campaign has no signature_template, the body is returned unchanged.
     */
    public function appendSignature(string $bodyHtml, Campaign $campaign, ?string $fromName, ?string $fromAddress): string
    {
        $sig = trim((string) ($campaign->signature_template ?? ''));
        if ($sig === '') return $bodyHtml;

        $sig = str_replace(
            ['{smtp_from_name}', '{smtp_from_address}'],
            [$fromName ?? '', $fromAddress ?? ''],
            $sig
        );

        // Avoid double-appending if AI already echoed the signature literally
        if (mb_stripos($bodyHtml, $sig) !== false) {
            return $bodyHtml;
        }

        return rtrim($bodyHtml) . "\n" . $sig;
    }

    private function applySmtpConfigFromAccount(SmtpAccount $a): void
    {
        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.host'       => $a->host,
            'mail.mailers.smtp.port'       => $a->port,
            'mail.mailers.smtp.username'   => $a->username,
            'mail.mailers.smtp.password'   => $a->password,
            'mail.mailers.smtp.encryption' => $a->encryption === 'none' ? null : $a->encryption,
            'mail.from.address'            => $a->from_address,
            'mail.from.name'               => $a->from_name,
        ]);
        Mail::purge('smtp');
    }

    private function applyEnvSmtpConfig(): void
    {
        $env = (new SettingsController)->readEnvValues([
            'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD',
            'MAIL_ENCRYPTION', 'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
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
        Mail::purge('smtp');
    }
}
