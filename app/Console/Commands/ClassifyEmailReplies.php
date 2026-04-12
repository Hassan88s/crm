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

    private const ALLOWED_LABELS = ['Interested','Not Interested','Info Request','Out of Office','Spam','Negative'];

    public function handle(): int
    {
        $settings = (new SettingsController)->readEnvValues([
            'IMAP_HOST','IMAP_PORT','IMAP_USERNAME','IMAP_PASSWORD','IMAP_ENCRYPTION','OPENAI_API_KEY',
        ]);

        $host     = $settings['IMAP_HOST']     ?? '';
        $port     = $settings['IMAP_PORT']     ?? '993';
        $user     = $settings['IMAP_USERNAME'] ?? '';
        $pass     = $settings['IMAP_PASSWORD'] ?? '';
        $enc      = $settings['IMAP_ENCRYPTION'] ?? 'ssl';
        $apiKey   = $settings['OPENAI_API_KEY'] ?? env('OPENAI_API_KEY', '');

        if (!$host || !$user || !$pass) {
            $this->error('IMAP not configured.');
            return 1;
        }
        if (!$apiKey) {
            $this->error('OPENAI_API_KEY not set.');
            return 1;
        }

        $flags   = match($enc) {
            'ssl'      => '/imap/ssl/novalidate-cert',
            'tls'      => '/imap/tls/novalidate-cert',
            'starttls' => '/imap/starttls/novalidate-cert',
            default    => '/imap/notls',
        };
        $mailbox = '{' . $host . ':' . $port . $flags . '}INBOX';
        $imap    = @\imap_open($mailbox, $user, $pass, 0, 1);

        if (!$imap) {
            $this->error('IMAP connect failed: ' . \imap_last_error());
            return 1;
        }

        $total  = \imap_num_msg($imap);
        $limit  = (int) $this->option('limit');
        $from   = max(1, $total - $limit + 1);
        $processed = 0;
        $skipped   = 0;
        $failed    = 0;

        $this->info("Found {$total} message(s). Scanning last {$limit}...");

        for ($msgNo = $total; $msgNo >= $from; $msgNo--) {
            // Get Message-ID header for deduplication
            $rawHeader = \imap_fetchheader($imap, $msgNo);
            $msgId     = $this->extractHeader($rawHeader, 'Message-ID');
            if (!$msgId) $msgId = 'msg-' . $msgNo . '-' . md5($rawHeader);

            // Skip if already classified
            if (EmailReply::where('message_id', $msgId)->exists()) {
                $skipped++;
                continue;
            }

            // Parse header info
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

            // Match speaker
            $speaker = $fromEmail ? Speaker::where('email', $fromEmail)->first() : null;

            // Extract body
            $structure = \imap_fetchstructure($imap, $msgNo);
            $bodyPlain = $this->extractPlainBody($imap, $msgNo, $structure);
            $bodyPlain = $this->cleanBody($bodyPlain);

            if (empty(trim($bodyPlain))) {
                $skipped++;
                continue;
            }

            // Classify with AI
            [$category, $score, $raw] = $this->classify($bodyPlain, $subject, $apiKey);

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

            $this->line("  [{$category}] ({$score}) {$fromEmail} — {$subject}");
            $processed++;
        }

        \imap_close($imap);

        $this->info("Done. Classified: {$processed}, Skipped (already done): {$skipped}, Failed: {$failed}");
        return 0;
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
                . "- Negative\n\n"
                . "Also give a lead score from 0 to 100 reflecting intent strength.\n\n"
                . "Rules:\n"
                . "- Interested: they want to speak, accept, show genuine interest\n"
                . "- Not Interested: politely declines, not relevant, not available\n"
                . "- Info Request: asking for more details, agenda, brochure, fees\n"
                . "- Out of Office: auto-reply, away message, vacation response\n"
                . "- Spam: unrelated, phishing, marketing blast, auto-promo\n"
                . "- Negative: angry, upset, unsubscribe request, rude tone\n\n"
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
            if ($charset !== 'UTF-8') $decoded = @mb_convert_encoding($decoded, 'UTF-8', $charset) ?: $decoded;
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
