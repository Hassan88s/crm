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

        // Build the full conversation between us and this contact.
        // Inbound = email_replies (any except 'No Reply' placeholders)
        // Outbound = email_logs sent successfully
        $email = strtolower($reply->from_email);

        $inbound = EmailReply::whereRaw('LOWER(from_email) = ?', [$email])
            ->where('category', '!=', 'No Reply')
            ->orderBy('received_at')
            ->get()
            ->map(fn($r) => (object) [
                'direction' => 'inbound',
                'id'        => 'in-' . $r->id,
                'subject'   => $r->subject,
                'body'      => $r->body_plain,
                'is_html'   => false,
                'from_name' => $r->from_name ?: $r->from_email,
                'from_email'=> $r->from_email,
                'at'        => $r->received_at,
                'category'  => $r->category,
                'reply_ref' => $r,
            ]);

        $outbound = EmailLog::whereRaw('LOWER(to_email) = ?', [$email])
            ->where('status', 'sent')
            ->orderBy('created_at')
            ->get()
            ->map(fn($l) => (object) [
                'direction'   => 'outbound',
                'id'          => 'out-' . $l->id,
                'subject'     => $l->subject,
                'body'        => $l->body,
                'is_html'     => $this->looksLikeHtml($l->body),
                'to_name'     => $l->to_name,
                'to_email'    => $l->to_email,
                'at'          => $l->created_at,
                'smtp_account'=> $l->smtpAccount?->name ?? ($l->smtp_account_id ? '#'.$l->smtp_account_id : null),
            ]);

        $thread = $inbound->concat($outbound)
            ->sortBy(fn($m) => $m->at?->timestamp ?? 0)
            ->values();

        return view('admin.replies.show', compact('reply', 'thread'));
    }

    private function looksLikeHtml(?string $s): bool
    {
        if (!$s) return false;
        return (bool) preg_match('/<\s*(p|br|div|table|ul|ol|li|strong|em|a|h[1-6])\b/i', $s);
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
    //
    // Logic: A speaker appears in "No Reply" if their LAST sent email is
    // more recent than their LAST reply. So:
    //   - Email sent → appears in "No Reply"
    //   - Reply received → removed from "No Reply"
    //   - Follow-up email sent after reply → appears in "No Reply" again
    //   - New reply to follow-up → removed again
    // ─────────────────────────────────────────────────────────────────────────

    private function syncNoReplyEntries(): void
    {
        // Last sent email per recipient (most recent send)
        $lastSent = EmailLog::where('status', 'sent')
            ->select('to_email', 'to_name', 'speaker_id', 'subject')
            ->selectRaw('MAX(created_at) as last_sent_at')
            ->groupBy('to_email', 'to_name', 'speaker_id', 'subject')
            ->get()
            ->keyBy(fn($r) => strtolower($r->to_email));

        // Last real reply per sender (ignoring "No Reply" placeholders)
        $lastReply = EmailReply::where('category', '!=', 'No Reply')
            ->select('from_email')
            ->selectRaw('MAX(received_at) as last_reply_at')
            ->groupBy('from_email')
            ->get()
            ->keyBy(fn($r) => strtolower($r->from_email));

        foreach ($lastSent as $email => $log) {
            $lastSentAt  = $log->last_sent_at;
            $lastReplyAt = $lastReply[$email]->last_reply_at ?? null;

            // Determine if we're currently awaiting a reply
            $awaitingReply = !$lastReplyAt || strtotime($lastSentAt) > strtotime($lastReplyAt);

            // Find existing "No Reply" entry for this email (if any)
            $existing = EmailReply::where('from_email', $log->to_email)
                ->where('category', 'No Reply')
                ->first();

            if ($awaitingReply) {
                // Should be in "No Reply" — create or update
                if ($existing) {
                    // Update the existing entry with latest send info
                    $existing->update([
                        'subject'     => 'No reply to: ' . ($log->subject ?? '(no subject)'),
                        'received_at' => $lastSentAt,
                        'classified_at' => now(),
                    ]);
                } else {
                    EmailReply::create([
                        'message_id'    => 'no-reply-' . md5($email . '-' . $lastSentAt),
                        'speaker_id'    => $log->speaker_id,
                        'from_email'    => $log->to_email,
                        'from_name'     => $log->to_name,
                        'subject'       => 'No reply to: ' . ($log->subject ?? '(no subject)'),
                        'body_plain'    => 'This speaker was emailed but has not replied yet.',
                        'received_at'   => $lastSentAt,
                        'category'      => 'No Reply',
                        'ai_score'      => null,
                        'ai_raw'        => null,
                        'classified_at' => now(),
                    ]);
                }
            } else {
                // Reply received after last send — remove from "No Reply"
                if ($existing) {
                    $existing->delete();
                }
            }
        }
    }

    // ── Send reply to an email ──────────────────────────────────────────────

    public function sendReply(Request $request, EmailReply $reply)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:255',
            'body'    => 'required|string',
        ]);

        // Try to use a rotating SMTP account; fall back to .env
        $smtpAccount = \App\Models\SmtpAccount::nextForRotation();

        if ($smtpAccount) {
            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.host'       => $smtpAccount->host,
                'mail.mailers.smtp.port'       => $smtpAccount->port,
                'mail.mailers.smtp.username'   => $smtpAccount->username,
                'mail.mailers.smtp.password'   => $smtpAccount->password,
                'mail.mailers.smtp.encryption' => $smtpAccount->encryption === 'none' ? null : $smtpAccount->encryption,
                'mail.from.address'            => $smtpAccount->from_address,
                'mail.from.name'               => $smtpAccount->from_name,
            ]);
            Mail::purge('smtp');
        } else {
            // Fallback to .env SMTP
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
            Mail::purge('smtp');
        }

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
                'speaker_id'      => $reply->speaker_id,
                'smtp_account_id' => $smtpAccount?->id,
                'to_email'        => $toEmail,
                'to_name'         => $toName,
                'subject'         => $subject,
                'body'            => $body,
                'status'          => 'sent',
            ]);

            return back()->with('reply_success', 'Reply sent successfully to ' . $toEmail);
        } catch (\Exception $e) {
            EmailLog::create([
                'speaker_id'      => $reply->speaker_id,
                'smtp_account_id' => $smtpAccount?->id,
                'to_email'        => $toEmail,
                'to_name'         => $toName,
                'subject'         => $subject,
                'body'            => $body,
                'status'          => 'failed',
                'error'           => $e->getMessage(),
            ]);

            return back()->with('reply_error', 'Failed to send reply: ' . $e->getMessage());
        }
    }

    // ── Delete one ─────────────────────────────────────────────────────────

    public function destroy(EmailReply $reply)
    {
        $reply->delete();
        return redirect()->route('admin.replies.index')
            ->with('success', 'Reply deleted.');
    }

    // ── Delete all (optionally scoped to a category) ───────────────────────

    public function destroyAll(Request $request)
    {
        $category = $request->input('category');
        $allowed  = ['Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Manual Review','No Reply'];

        if ($category && in_array($category, $allowed, true)) {
            $count = EmailReply::where('category', $category)->delete();
            return redirect()->route('admin.replies.index', ['category' => $category])
                ->with('success', "Deleted {$count} reply(ies) in category \"{$category}\".");
        }

        $count = EmailReply::query()->delete();
        return redirect()->route('admin.replies.index')
            ->with('success', "Deleted {$count} reply(ies).");
    }
}
