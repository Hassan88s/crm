<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailReply;
use App\Models\Speaker;
use App\Http\Controllers\SettingsController;

class ClassifyEmailReplies extends Command
{
    protected $signature   = 'emails:classify-replies {--limit=100 : Max messages to scan}';
    protected $description = 'Fetch emails from IMAP and classify replies with AI';

    private const ALLOWED_LABELS = ['Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Bounced'];

    public function handle(): int
    {
        $settings = (new SettingsController)->readEnvValues(['OPENAI_API_KEY']);
        $apiKey   = $settings['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');
        if (!$apiKey) {
            $this->error('OPENAI_API_KEY not set.');
            return 1;
        }

        // Collect active accounts from DB; fall back to .env if none
        $accounts = \App\Models\ImapAccount::active()->get();
        if ($accounts->isEmpty()) {
            $env = (new SettingsController)->readEnvValues([
                'IMAP_HOST','IMAP_PORT','IMAP_USERNAME','IMAP_PASSWORD','IMAP_ENCRYPTION',
            ]);
            if (empty($env['IMAP_HOST']) || empty($env['IMAP_USERNAME'])) {
                $this->error('No IMAP accounts configured and .env IMAP missing.');
                return 1;
            }
            $fake = new \App\Models\ImapAccount([
                'name'       => 'Default',
                'host'       => $env['IMAP_HOST'],
                'port'       => (int)($env['IMAP_PORT'] ?? 993),
                'username'   => $env['IMAP_USERNAME'],
                'password'   => $env['IMAP_PASSWORD'] ?? '',
                'encryption' => $env['IMAP_ENCRYPTION'] ?? 'ssl',
                'is_active'  => true,
            ]);
            $accounts = collect([$fake]);
        }

        $limit     = (int) $this->option('limit');
        $processed = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($accounts as $account) {
            $this->info("Connecting to {$account->name} ({$account->username})...");
            $imap = $account->openConnection('INBOX');
            if (!$imap) {
                $this->error("  IMAP connect failed: " . \imap_last_error());
                $failed++;
                continue;
            }

            $total = \imap_num_msg($imap);
            $from  = max(1, $total - $limit + 1);
            $this->info("  Found {$total} message(s). Scanning last {$limit}...");

            for ($msgNo = $total; $msgNo >= $from; $msgNo--) {
                $rawHeader = \imap_fetchheader($imap, $msgNo);
                $msgId     = $this->extractHeader($rawHeader, 'Message-ID');
                if (!$msgId) $msgId = 'msg-' . $account->id . '-' . $msgNo . '-' . md5($rawHeader);

                if (EmailReply::where('message_id', $msgId)->exists()) {
                    $skipped++;
                    continue;
                }

                $header   = @\imap_headerinfo($imap, $msgNo);
                $fromAddr = $header->from[0] ?? null;
                $fromEmail = $fromAddr ? strtolower(trim($fromAddr->mailbox . '@' . $fromAddr->host)) : '';
                $fromName  = $fromAddr ? @\imap_utf8($fromAddr->personal ?? '') : '';
                $subject   = @\imap_utf8($header->subject ?? '(no subject)');
                $date      = $header->date ?? now()->toRfc2822String();

                try {
                    $receivedAt = new \DateTime($date);
                } catch (\Exception $e) {
                    $receivedAt = now();
                }

                $speaker = $fromEmail ? Speaker::where('email', $fromEmail)->first() : null;

                $structure = \imap_fetchstructure($imap, $msgNo);
                $bodyPlain = $this->extractPlainBody($imap, $msgNo, $structure);
                $bodyPlain = $this->cleanBody($bodyPlain);

                if (empty(trim($bodyPlain))) {
                    $skipped++;
                    continue;
                }

                // Heuristic pre-check for obvious bounces — saves an OpenAI call
                if ($this->looksLikeBounce($fromEmail, $subject, $bodyPlain, $rawHeader)) {
                    $category = 'Bounced';
                    $score    = 100;
                    $raw      = ['heuristic' => true, 'reason' => 'mailer-daemon / DSN markers'];
                } else {
                    [$category, $score, $raw] = $this->classify($bodyPlain, $subject, $apiKey);
                }

                EmailReply::create([
                    'message_id'    => $msgId,
                    'speaker_id'    => $speaker?->id,
                    'from_email'    => $fromEmail,
                    'from_name'     => $fromName ?: null,
                    'subject'       => $subject,
                    'body_plain'    => $bodyPlain,
                    'received_at'   => $receivedAt,
                    'category'      => $category,
                    'ai_score'      => $score,
                    'ai_raw'        => $raw,
                    'classified_at' => now(),
                ]);

                $this->line("    [{$category}] ({$score}) {$fromEmail} — {$subject}");
                $processed++;
            }

            \imap_close($imap);
            if ($account->exists) {
                $account->update(['last_fetched_at' => now()]);
            }
        }

        $this->info("Done. Classified: {$processed}, Skipped (already done): {$skipped}, Failed: {$failed}");
        return 0;
    }

    /**
     * Quick non-AI check for bounce / NDR (Non-Delivery Report) emails.
     * Returns true on strong, obvious signals so we skip the OpenAI call.
     */
    private function looksLikeBounce(string $fromEmail, string $subject, string $body, string $rawHeader): bool
    {
        $from = strtolower($fromEmail);
        $sub  = strtolower($subject);
        $head = strtolower($rawHeader);

        // 1) Sender is a postmaster / mailer-daemon
        $senderMarkers = ['mailer-daemon', 'postmaster', 'mail-daemon', 'mail delivery system', 'noreply-bounces', 'bounces+'];
        foreach ($senderMarkers as $m) {
            if (str_contains($from, $m)) return true;
        }

        // 2) Subject contains classic NDR phrases
        $subjectMarkers = [
            'undeliverable', 'undelivered mail', 'mail delivery failed', 'mail delivery failure',
            'delivery status notification', 'returned mail', 'could not be delivered',
            'returned to sender', 'failure notice', 'delivery has failed',
        ];
        foreach ($subjectMarkers as $m) {
            if (str_contains($sub, $m)) return true;
        }

        // 3) RFC 3464 headers (DSN content type / Auto-Submitted)
        if (str_contains($head, 'content-type: multipart/report') && str_contains($head, 'report-type=delivery-status')) {
            return true;
        }
        if (preg_match('/auto-submitted:\s*auto-replied|auto-generated/i', $head)) {
            // auto-replied alone usually = Out of Office, NOT a bounce — only treat as bounce
            // if combined with one of the body markers below.
        }

        // 4) Body has SMTP failure codes or recipient-rejection language
        $bodyMarkers = [
            "550 5.1.1", "550 5.7", "554 5.", "5.1.1", "5.0.0",
            'recipient address rejected', "user unknown", "no such user",
            "address not found", "mailbox unavailable", "does not exist",
        ];
        $bodyLower = mb_strtolower($body);
        foreach ($bodyMarkers as $m) {
            if (str_contains($bodyLower, $m)) return true;
        }

        return false;
    }

    // ── Classify via OpenAI ──────────────────────────────────────────────────

    private function classify(string $body, string $subject, string $apiKey): array
    {
        $prompt = "You are an email intent classifier for a speaker CRM system.\n"
                . "Classify the email reply below into EXACTLY ONE of these categories:\n"
                . "- Interested\n"
                . "- Not Interested\n"
                . "- Info Request\n"
                . "- Out of Office\n"
                . "- Spam\n"
                . "- Negative\n"
                . "- Bounced\n\n"
                . "Also give a lead score from 0 to 100 reflecting intent strength.\n\n"
                . "Rules:\n"
                . "- Interested: they want to speak, accept, show genuine interest\n"
                . "- Not Interested: politely declines, not relevant, not available\n"
                . "- Info Request: asking for more details, agenda, brochure, fees\n"
                . "- Out of Office: auto-reply, away message, vacation response\n"
                . "- Spam: unrelated, phishing, marketing blast, auto-promo\n"
                . "- Negative: angry, upset, unsubscribe request, rude tone\n"
                . "- Bounced: automated non-delivery report from a mail server. Strong signals: sender contains \"mailer-daemon\", \"postmaster\", \"mail-daemon\"; subject contains \"undeliverable\", \"mail delivery failed\", \"delivery status notification\", \"returned mail\", \"could not be delivered\"; body mentions SMTP error codes (550, 5.1.1, 5.0.0, etc.) or \"recipient address rejected\".\n\n"
                . "Return ONLY valid JSON in this exact format (no markdown, no explanation):\n"
                . "{\"label\":\"Interested\",\"score\":87}\n\n"
                . "Subject: {$subject}\n\n"
                . "Email body:\n{$body}";

        $payload = json_encode([
            'model'       => 'gpt-4o-mini',
            'messages'    => [
                ['role' => 'system', 'content' => 'You are an email intent classifier. Return only valid JSON.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature' => 0,
            'max_tokens'  => 60,
        ]);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            if (!$response) continue;

            $json    = json_decode($response, true);
            $content = $json['choices'][0]['message']['content'] ?? '';
            $content = trim(preg_replace('/```json?|```/', '', $content));
            $result  = json_decode($content, true);

            if (
                is_array($result)
                && isset($result['label'], $result['score'])
                && in_array($result['label'], self::ALLOWED_LABELS)
                && is_numeric($result['score'])
                && $result['score'] >= 0
                && $result['score'] <= 100
            ) {
                return [$result['label'], (int)$result['score'], $result];
            }
        }

        return ['Manual Review', null, null];
    }

    // ── Body extraction ──────────────────────────────────────────────────────

    private function extractPlainBody($imap, int $msgNo, $structure): string
    {
        if (isset($structure->parts)) {
            return $this->getPlainFromParts($imap, $msgNo, $structure->parts, '');
        }
        $raw = \imap_fetchbody($imap, $msgNo, '1');
        return match($structure->encoding) {
            3 => base64_decode($raw),
            4 => quoted_printable_decode($raw),
            default => $raw,
        };
    }

    private function getPlainFromParts($imap, int $msgNo, array $parts, string $prefix): string
    {
        $plain = '';
        $html  = '';
        foreach ($parts as $i => $part) {
            $section = $prefix === '' ? (string)($i + 1) : $prefix . '.' . ($i + 1);
            if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') continue;
            if (isset($part->parts)) {
                $nested = $this->getPlainFromParts($imap, $msgNo, $part->parts, $section);
                if ($nested && !$plain) $plain = $nested;
                continue;
            }
            $type = strtolower($part->subtype ?? '');
            $raw  = \imap_fetchbody($imap, $msgNo, $section);
            $decoded = match($part->encoding) {
                3 => base64_decode($raw),
                4 => quoted_printable_decode($raw),
                default => $raw,
            };
            $charset = 'UTF-8';
            if (isset($part->parameters)) {
                foreach ($part->parameters as $p) {
                    if (strtolower($p->attribute) === 'charset') { $charset = strtoupper($p->value); break; }
                }
            }
            $decoded = \App\Support\Charset::toUtf8($decoded, $charset);
            if ($type === 'plain' && !$plain) $plain = $decoded;
            if ($type === 'html'  && !$html)  $html  = $decoded;
        }
        if ($plain) return $plain;
        if ($html)  return strip_tags($html);
        return '';
    }

    private function cleanBody(string $body): string
    {
        // Strip HTML if any slipped through
        $body = strip_tags($body);
        // Remove quoted reply lines (> prefix)
        $lines = explode("\n", $body);
        $clean = [];
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '>')) continue;
            // Stop at "On ... wrote:" markers
            if (preg_match('/^On .{5,100} wrote:/i', $trimmed)) break;
            if (preg_match('/^-{3,}\s*(Original Message|Forwarded)/i', $trimmed)) break;
            $clean[] = $line;
        }
        $body = implode("\n", $clean);
        // Collapse whitespace
        $body = preg_replace('/\n{3,}/', "\n\n", $body);
        // Limit to first 1500 chars for AI
        return trim(mb_substr($body, 0, 1500));
    }

    private function extractHeader(string $raw, string $name): string
    {
        if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.+?)(?=\r?\n\S|\r?\n\r?\n)/ims', $raw, $m)) {
            return trim(preg_replace('/\s+/', ' ', $m[1]));
        }
        return '';
    }
}
