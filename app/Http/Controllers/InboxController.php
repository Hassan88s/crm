<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Speaker;
use App\Models\EmailReply;
use App\Models\EmailLog;
use App\Models\ImapAccount;

class InboxController extends Controller
{
    // ── Friendly name → IMAP folder candidates ─────────────────────────────

    private const FOLDER_MAP = [
        'inbox'  => ['INBOX'],
        'sent'   => ['Sent', 'Sent Mail', 'Sent Items', '[Gmail]/Sent Mail', 'INBOX.Sent'],
        'drafts' => ['Drafts', '[Gmail]/Drafts', 'INBOX.Drafts'],
        'spam'   => ['Spam', 'Junk', 'Junk E-mail', '[Gmail]/Spam', 'INBOX.Spam', 'INBOX.Junk'],
        'trash'  => ['Trash', 'Deleted Items', 'Deleted Messages', '[Gmail]/Trash', 'INBOX.Trash'],
    ];

    private const FOLDER_LABELS = [
        'inbox'  => 'Inbox',
        'sent'   => 'Sent',
        'drafts' => 'Drafts',
        'spam'   => 'Spam',
        'trash'  => 'Trash',
    ];

    // ── IMAP Connection ─────────────────────────────────────────────────────

    private function getImapSettings(): array
    {
        return (new SettingsController)->readEnvValues([
            'IMAP_HOST', 'IMAP_PORT', 'IMAP_USERNAME', 'IMAP_PASSWORD', 'IMAP_ENCRYPTION',
        ]);
    }

    private function buildMailbox(array $settings, string $folder = 'INBOX'): string
    {
        $host = $settings['IMAP_HOST'] ?? '';
        $port = $settings['IMAP_PORT'] ?? '993';
        $enc  = $settings['IMAP_ENCRYPTION'] ?? 'ssl';

        $flags = match ($enc) {
            'ssl'      => '/imap/ssl/novalidate-cert',
            'tls'      => '/imap/tls/novalidate-cert',
            'starttls' => '/imap/starttls/novalidate-cert',
            default    => '/imap/notls',
        };

        return '{' . $host . ':' . $port . $flags . '}' . $folder;
    }

    private function serverRef(array $settings): string
    {
        $host = $settings['IMAP_HOST'] ?? '';
        $port = $settings['IMAP_PORT'] ?? '993';
        $enc  = $settings['IMAP_ENCRYPTION'] ?? 'ssl';
        $flags = match ($enc) {
            'ssl'      => '/imap/ssl/novalidate-cert',
            'tls'      => '/imap/tls/novalidate-cert',
            'starttls' => '/imap/starttls/novalidate-cert',
            default    => '/imap/notls',
        };
        return '{' . $host . ':' . $port . $flags . '}';
    }

    private function connect(string $folder = 'INBOX')
    {
        $settings = $this->getImapSettings();
        $host     = $settings['IMAP_HOST']     ?? '';
        $user     = $settings['IMAP_USERNAME'] ?? '';
        $pass     = $settings['IMAP_PASSWORD'] ?? '';

        if (!$host || !$user || !$pass) return false;

        $mailbox = $this->buildMailbox($settings, $folder);
        return @\imap_open($mailbox, $user, $pass, 0, 1);
    }

    /**
     * Return the list of IMAP accounts to use.
     * If DB accounts exist (active), use them. Otherwise fall back to a
     * synthetic account built from .env so existing setups keep working.
     */
    private function activeAccounts()
    {
        $accounts = ImapAccount::active()->get();
        if ($accounts->isNotEmpty()) return $accounts;

        // Fallback: synthesize an account from .env
        $settings = $this->getImapSettings();
        if (empty($settings['IMAP_HOST']) || empty($settings['IMAP_USERNAME'])) {
            return collect();
        }

        $fake = new ImapAccount([
            'name'       => 'Default',
            'host'       => $settings['IMAP_HOST'],
            'port'       => (int)($settings['IMAP_PORT'] ?? 993),
            'username'   => $settings['IMAP_USERNAME'],
            'password'   => $settings['IMAP_PASSWORD'] ?? '',
            'encryption' => $settings['IMAP_ENCRYPTION'] ?? 'ssl',
            'color'      => '#2563eb',
            'is_active'  => true,
        ]);
        $fake->id = 0; // sentinel for .env fallback
        return collect([$fake]);
    }

    private function findAccount(?int $id): ?ImapAccount
    {
        $accounts = $this->activeAccounts();
        if ($id === null) return $accounts->first();

        foreach ($accounts as $acc) {
            if ((int)$acc->id === (int)$id) return $acc;
        }
        return $accounts->first();
    }

    /**
     * Resolve a friendly folder for a specific account.
     */
    private function resolveFolderForAccount(ImapAccount $account, string $friendly): ?string
    {
        if ($friendly === 'inbox') return 'INBOX';

        $candidates = self::FOLDER_MAP[$friendly] ?? [];

        // Discover this account's folders
        $imap = $account->openConnection('INBOX');
        if (!$imap) return null;

        $ref = $account->imapServerRef();
        $rawList = @\imap_list($imap, $ref, '*') ?: [];
        @\imap_close($imap);

        $available = [];
        foreach ($rawList as $raw) {
            $name = str_replace($ref, '', $raw);
            $name = @\imap_utf7_decode($name) ?: $name;
            $available[] = $name;
        }

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $available)) return $candidate;
        }
        foreach ($candidates as $candidate) {
            foreach ($available as $avail) {
                if (strtolower($avail) === strtolower($candidate)) return $avail;
            }
        }
        return null;
    }

    // ── Discover available IMAP folders ─────────────────────────────────────

    private function discoverFolders(): array
    {
        // Return from session cache if available
        $cached = session('imap_folders');
        if ($cached) return $cached;

        $settings = $this->getImapSettings();
        $host     = $settings['IMAP_HOST']     ?? '';
        $user     = $settings['IMAP_USERNAME'] ?? '';
        $pass     = $settings['IMAP_PASSWORD'] ?? '';

        if (!$host || !$user || !$pass) return [];

        $ref     = $this->serverRef($settings);
        $mailbox = $this->buildMailbox($settings, 'INBOX');
        $imap    = @\imap_open($mailbox, $user, $pass, 0, 1);
        if (!$imap) return [];

        $rawList = @\imap_list($imap, $ref, '*') ?: [];
        \imap_close($imap);

        // Strip server reference to get clean folder names
        $folders = [];
        foreach ($rawList as $raw) {
            $name = str_replace($ref, '', $raw);
            // Decode UTF-7 folder names
            $name = @\imap_utf7_decode($name) ?: $name;
            $folders[] = $name;
        }

        session(['imap_folders' => $folders]);
        return $folders;
    }

    /**
     * Resolve a friendly folder name (inbox, sent, spam, trash, drafts) to the
     * actual IMAP folder name available on the server.
     */
    private function resolveFolder(string $friendly): ?string
    {
        if ($friendly === 'inbox') return 'INBOX';

        $candidates = self::FOLDER_MAP[$friendly] ?? [];
        $available  = $this->discoverFolders();

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $available)) {
                return $candidate;
            }
        }

        // Try case-insensitive match
        foreach ($candidates as $candidate) {
            foreach ($available as $avail) {
                if (strtolower($avail) === strtolower($candidate)) return $avail;
            }
        }

        return null;
    }

    /**
     * Build the folder list with real IMAP names + availability + unread counts.
     */
    private function buildFolderList(): array
    {
        $result = [];
        foreach (self::FOLDER_LABELS as $key => $label) {
            $imapName = $this->resolveFolder($key);
            $result[$key] = [
                'key'       => $key,
                'label'     => $label,
                'imap_name' => $imapName,
                'available' => $imapName !== null,
                'unread'    => 0,
                'total'     => 0,
            ];
        }

        // Get unread count for inbox
        if ($result['inbox']['available']) {
            $imap = $this->connect('INBOX');
            if ($imap) {
                $result['inbox']['total']  = \imap_num_msg($imap);
                $unseen = \imap_search($imap, 'UNSEEN') ?: [];
                $result['inbox']['unread'] = count($unseen);
                \imap_close($imap);
            }
        }

        return $result;
    }

    // ── Inbox list ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $accounts = $this->activeAccounts();
        $configured = $accounts->isNotEmpty();
        $settings = $this->getImapSettings();

        $emails   = [];
        $error    = null;
        $total    = 0;
        $unread   = 0;
        $page     = max(1, (int) $request->get('page', 1));
        $perPage  = 20;

        $folder = $request->get('folder', 'inbox');
        if (!array_key_exists($folder, self::FOLDER_LABELS)) $folder = 'inbox';

        $folders     = [];
        $folderLabel = self::FOLDER_LABELS[$folder];

        // Initial folder list (uses first account for display purposes)
        foreach (self::FOLDER_LABELS as $key => $label) {
            $folders[$key] = [
                'key'       => $key,
                'label'     => $label,
                'imap_name' => $key === 'inbox' ? 'INBOX' : null,
                'available' => true,
                'unread'    => 0,
                'total'     => 0,
            ];
        }

        if ($configured) {
            $errors = [];
            foreach ($accounts as $account) {
                $imapFolder = $this->resolveFolderForAccount($account, $folder);
                if (!$imapFolder) continue;

                $imap = $account->openConnection($imapFolder);
                if (!$imap) {
                    $errors[] = "{$account->name}: " . \imap_last_error();
                    continue;
                }

                $accountTotal = \imap_num_msg($imap);
                $unseen       = \imap_search($imap, 'UNSEEN') ?: [];
                $accountUnread = count($unseen);

                $total  += $accountTotal;
                $unread += $accountUnread;
                $folders[$folder]['total']  += $accountTotal;
                $folders[$folder]['unread'] += $accountUnread;

                if ($accountTotal > 0) {
                    // Fetch all overviews in reasonable range (last 200 per account)
                    $cap = min($accountTotal, 200);
                    $from = max(1, $accountTotal - $cap + 1);
                    $to   = $accountTotal;
                    $overview = \imap_fetch_overview($imap, "{$from}:{$to}", 0) ?: [];
                    foreach ($overview as $ov) {
                        $ov->_account_id    = $account->id;
                        $ov->_account_name  = $account->name;
                        $ov->_account_color = $account->color;
                        $ov->_ts            = strtotime($ov->date ?? 'now') ?: 0;
                        $emails[] = $ov;
                    }
                }

                \imap_close($imap);
            }

            if (!empty($errors) && empty($emails)) {
                $error = 'Could not connect to IMAP: ' . implode(' | ', $errors);
            }

            // Sort merged feed by date desc
            usort($emails, fn($a, $b) => ($b->_ts ?? 0) <=> ($a->_ts ?? 0));

            // Manual pagination
            $totalLoaded = count($emails);
            $start       = ($page - 1) * $perPage;
            $emails      = array_slice($emails, $start, $perPage);
            // Override total/lastPage to reflect what was actually loaded
            $total = $totalLoaded;
        }

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return view('admin.inbox.index', compact(
            'emails', 'error', 'configured', 'total', 'unread',
            'page', 'perPage', 'lastPage', 'settings',
            'folder', 'folders', 'folderLabel', 'accounts'
        ));
    }

    // ── Single email ────────────────────────────────────────────────────────

    public function show(Request $request, int $msgNo)
    {
        $folder     = $request->get('folder', 'inbox');
        if (!array_key_exists($folder, self::FOLDER_LABELS)) $folder = 'inbox';
        $folderLabel = self::FOLDER_LABELS[$folder];

        $accountId = $request->query('account_id');
        $account   = $this->findAccount($accountId !== null ? (int)$accountId : null);
        if (!$account) {
            return redirect()->route('admin.inbox.index', ['folder' => $folder])
                ->with('error', 'No IMAP account configured.');
        }

        $imapFolder = $this->resolveFolderForAccount($account, $folder) ?? 'INBOX';
        $imap = $account->openConnection($imapFolder);

        if (!$imap) {
            return redirect()->route('admin.inbox.index', ['folder' => $folder])
                ->with('error', 'Could not connect: ' . \imap_last_error());
        }

        $total = \imap_num_msg($imap);
        if ($msgNo < 1 || $msgNo > $total) {
            \imap_close($imap);
            return redirect()->route('admin.inbox.index', ['folder' => $folder])
                ->with('error', 'Email not found.');
        }

        $header    = \imap_headerinfo($imap, $msgNo);
        $structure = \imap_fetchstructure($imap, $msgNo);

        // Mark as read
        \imap_setflag_full($imap, (string)$msgNo, '\\Seen');

        // Parse body
        $body        = $this->getBody($imap, $msgNo, $structure);
        $isHtml      = $this->hasHtml($structure);
        $attachments = $this->getAttachments($imap, $msgNo, $structure);

        \imap_close($imap);

        $uid = $msgNo;
        return view('admin.inbox.show', compact(
            'header', 'body', 'isHtml', 'uid', 'attachments',
            'folder', 'folderLabel', 'account'
        ));
    }

    // ── Move email between folders ──────────────────────────────────────────

    public function move(Request $request, int $msgNo)
    {
        $fromFolder = $request->input('from_folder', 'inbox');
        $toFolder   = $request->input('to_folder', 'trash');

        $accountId = $request->input('account_id');
        $account   = $this->findAccount($accountId !== null ? (int)$accountId : null);
        if (!$account) {
            return back()->with('error', 'No IMAP account configured.');
        }

        $fromImap = $this->resolveFolderForAccount($account, $fromFolder);
        $toImap   = $this->resolveFolderForAccount($account, $toFolder);

        if (!$fromImap || !$toImap) {
            return back()->with('error', 'Folder not available on this server.');
        }

        $imap = $account->openConnection($fromImap);
        if (!$imap) {
            return back()->with('error', 'Could not connect: ' . \imap_last_error());
        }

        $result = @\imap_mail_move($imap, (string)$msgNo, $toImap);
        \imap_expunge($imap);
        \imap_close($imap);

        $toLabel = self::FOLDER_LABELS[$toFolder] ?? $toFolder;

        if ($result) {
            return redirect()->route('admin.inbox.index', ['folder' => $fromFolder])
                ->with('success', "Email moved to {$toLabel}.");
        }

        return redirect()->route('admin.inbox.index', ['folder' => $fromFolder])
            ->with('error', 'Failed to move email. ' . \imap_last_error());
    }

    // ── Mark as read ────────────────────────────────────────────────────────

    public function markRead(Request $request, int $msgNo)
    {
        $folder    = $request->get('folder', 'inbox');
        $accountId = $request->input('account_id');
        $account   = $this->findAccount($accountId !== null ? (int)$accountId : null);
        if (!$account) return response()->json(['ok' => false], 422);

        $imapFolder = $this->resolveFolderForAccount($account, $folder) ?? 'INBOX';
        $imap = $account->openConnection($imapFolder);
        if ($imap) {
            \imap_setflag_full($imap, (string)$msgNo, '\\Seen');
            \imap_close($imap);
        }
        return response()->json(['ok' => true]);
    }

    // ── Delete (permanent) ──────────────────────────────────────────────────

    public function destroy(Request $request, int $msgNo)
    {
        $folder    = $request->get('folder', 'inbox');
        $accountId = $request->input('account_id') ?? $request->query('account_id');
        $account   = $this->findAccount($accountId !== null ? (int)$accountId : null);
        if (!$account) {
            return back()->with('error', 'No IMAP account configured.');
        }

        $imapFolder = $this->resolveFolderForAccount($account, $folder) ?? 'INBOX';
        $imap = $account->openConnection($imapFolder);
        if ($imap) {
            \imap_delete($imap, (string)$msgNo);
            \imap_expunge($imap);
            \imap_close($imap);
        }
        return redirect()->route('admin.inbox.index', ['folder' => $folder])
            ->with('success', 'Email deleted permanently.');
    }

    // ── Contacts ────────────────────────────────────────────────────────────

    public function contacts(Request $request)
    {
        $search = $request->get('search', '');

        // Gather contacts from received emails (email_replies)
        $received = EmailReply::selectRaw('from_email as email, from_name as name, MAX(received_at) as last_contact, COUNT(*) as times, speaker_id')
            ->groupBy('from_email', 'from_name', 'speaker_id');

        // Gather contacts from sent emails (email_logs)
        $sent = EmailLog::selectRaw('to_email as email, to_name as name, MAX(created_at) as last_contact, COUNT(*) as times, speaker_id')
            ->where('status', 'sent')
            ->groupBy('to_email', 'to_name', 'speaker_id');

        // Merge into PHP collection
        $receivedData = $received->get()->map(function ($r) {
            return (object) [
                'email'        => strtolower($r->email),
                'name'         => $r->name ?: $r->email,
                'last_contact' => $r->last_contact,
                'times'        => $r->times,
                'speaker_id'   => $r->speaker_id,
                'source'       => 'Received',
            ];
        });

        $sentData = $sent->get()->map(function ($s) {
            return (object) [
                'email'        => strtolower($s->email),
                'name'         => $s->name ?: $s->email,
                'last_contact' => $s->last_contact,
                'times'        => $s->times,
                'speaker_id'   => $s->speaker_id,
                'source'       => 'Sent',
            ];
        });

        // Also add all speakers as contacts
        $speakerQuery = Speaker::query();
        if ($search) {
            $speakerQuery->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }
        $speakerContacts = $speakerQuery->get()->map(function ($sp) {
            return (object) [
                'email'        => strtolower($sp->email),
                'name'         => $sp->full_name,
                'last_contact' => null,
                'times'        => 0,
                'speaker_id'   => $sp->id,
                'source'       => 'Speaker',
                'company'      => $sp->company,
                'title'        => $sp->title,
            ];
        });

        // Merge all and deduplicate by email (prefer entry with most data)
        $merged = collect()
            ->concat($receivedData)
            ->concat($sentData)
            ->concat($speakerContacts)
            ->groupBy('email')
            ->map(function ($group) {
                // Pick the richest entry as base, sum times
                $best = $group->sortByDesc(function ($c) {
                    return ($c->speaker_id ? 10 : 0) + mb_strlen($c->name ?? '');
                })->first();

                $totalTimes  = $group->sum('times');
                $lastContact = $group->max('last_contact');
                $sources     = $group->pluck('source')->unique()->implode(', ');

                $best->times        = $totalTimes;
                $best->last_contact = $lastContact;
                $best->source       = $sources;
                $best->company      = $best->company ?? null;
                $best->title        = $best->title ?? null;

                // Load speaker data if we have speaker_id
                if ($best->speaker_id && empty($best->company)) {
                    $sp = Speaker::find($best->speaker_id);
                    if ($sp) {
                        $best->name    = $sp->full_name;
                        $best->company = $sp->company;
                        $best->title   = $sp->title;
                    }
                }

                return $best;
            })
            ->values()
            ->sortByDesc('last_contact');

        // Apply search filter
        if ($search) {
            $s = strtolower($search);
            $merged = $merged->filter(function ($c) use ($s) {
                return str_contains(strtolower($c->email), $s)
                    || str_contains(strtolower($c->name ?? ''), $s)
                    || str_contains(strtolower($c->company ?? ''), $s);
            });
        }

        $contacts    = $merged->values();
        $totalCount  = $contacts->count();
        $speakerCount = $contacts->filter(fn($c) => $c->speaker_id)->count();

        return view('admin.inbox.contacts', compact('contacts', 'search', 'totalCount', 'speakerCount'));
    }

    // ── Body parser ─────────────────────────────────────────────────────────

    private function getBody($imap, int $msgNo, $structure): string
    {
        if (isset($structure->parts) && count($structure->parts)) {
            return $this->getBodyFromParts($imap, $msgNo, $structure->parts, '');
        }

        $body = \imap_fetchbody($imap, $msgNo, '1');

        return match ($structure->encoding) {
            3 => base64_decode($body),
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function getBodyFromParts($imap, int $msgNo, array $parts, string $partNum): string
    {
        $html  = '';
        $plain = '';

        foreach ($parts as $i => $part) {
            $section = $partNum === '' ? (string)($i + 1) : $partNum . '.' . ($i + 1);

            if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
                continue;
            }

            if (isset($part->parts)) {
                $nested = $this->getBodyFromParts($imap, $msgNo, $part->parts, $section);
                if ($nested) $html = $html ?: $nested;
                continue;
            }

            $type = strtolower($part->subtype ?? '');
            $raw  = \imap_fetchbody($imap, $msgNo, $section);

            $decoded = match ($part->encoding) {
                3 => base64_decode($raw),
                4 => quoted_printable_decode($raw),
                default => $raw,
            };

            // Charset conversion
            $charset = 'UTF-8';
            if (isset($part->parameters)) {
                foreach ($part->parameters as $p) {
                    if (strtolower($p->attribute) === 'charset') {
                        $charset = strtoupper($p->value);
                        break;
                    }
                }
            }
            if ($charset !== 'UTF-8') {
                $decoded = @mb_convert_encoding($decoded, 'UTF-8', $charset) ?: $decoded;
            }

            if ($type === 'html') {
                $html = $decoded;
            } elseif ($type === 'plain' && !$plain) {
                $plain = $decoded;
            }
        }

        return $html ?: nl2br(htmlspecialchars($plain));
    }

    private function hasHtml($structure): bool
    {
        if (!isset($structure->parts)) {
            return strtolower($structure->subtype ?? '') === 'html';
        }
        foreach ($structure->parts as $part) {
            if (strtolower($part->subtype ?? '') === 'html') return true;
            if (isset($part->parts) && $this->hasHtml($part)) return true;
        }
        return false;
    }

    private function getAttachments($imap, int $msgNo, $structure): array
    {
        $attachments = [];
        if (!isset($structure->parts)) return $attachments;

        foreach ($structure->parts as $i => $part) {
            if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
                $name = '';
                if (isset($part->dparameters)) {
                    foreach ($part->dparameters as $dp) {
                        if (strtolower($dp->attribute) === 'filename') {
                            $name = \imap_utf8($dp->value);
                        }
                    }
                }
                if (!$name && isset($part->parameters)) {
                    foreach ($part->parameters as $p) {
                        if (strtolower($p->attribute) === 'name') {
                            $name = \imap_utf8($p->value);
                        }
                    }
                }
                $attachments[] = [
                    'name'     => $name ?: 'attachment',
                    'section'  => (string)($i + 1),
                    'encoding' => $part->encoding,
                    'bytes'    => $part->bytes ?? 0,
                    'subtype'  => strtolower($part->subtype ?? ''),
                ];
            }
        }

        return $attachments;
    }
}
