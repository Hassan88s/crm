<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImapAccount extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password',
        'encryption', 'color', 'is_active', 'last_fetched_at',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Build the IMAP mailbox string for a given folder.
     */
    public function imapMailbox(string $folder = 'INBOX'): string
    {
        $flags = match ($this->encryption) {
            'ssl'      => '/imap/ssl/novalidate-cert',
            'tls'      => '/imap/tls/novalidate-cert',
            'starttls' => '/imap/starttls/novalidate-cert',
            default    => '/imap/notls',
        };
        return '{' . $this->host . ':' . $this->port . $flags . '}' . $folder;
    }

    /**
     * Build just the server reference (no folder) — useful for imap_list().
     */
    public function imapServerRef(): string
    {
        $flags = match ($this->encryption) {
            'ssl'      => '/imap/ssl/novalidate-cert',
            'tls'      => '/imap/tls/novalidate-cert',
            'starttls' => '/imap/starttls/novalidate-cert',
            default    => '/imap/notls',
        };
        return '{' . $this->host . ':' . $this->port . $flags . '}';
    }

    /**
     * Open an IMAP connection to the given folder.
     * Returns the resource or null on failure. Caller must imap_close().
     */
    public function openConnection(string $folder = 'INBOX')
    {
        $mailbox = $this->imapMailbox($folder);
        $imap = @\imap_open($mailbox, $this->username, $this->password, 0, 1);
        return $imap ?: null;
    }
}
