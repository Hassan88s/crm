<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailReply extends Model
{
    protected $fillable = [
        'message_id','speaker_id','from_email','from_name','subject',
        'body_plain','received_at','category','ai_score','ai_raw','classified_at',
    ];

    protected $casts = [
        'received_at'   => 'datetime',
        'classified_at' => 'datetime',
        'ai_raw'        => 'array',
    ];

    public function speaker()
    {
        return $this->belongsTo(Speaker::class);
    }

    /**
     * Build a set of email addresses that have ever bounced.
     *
     * Bounce notifications usually arrive from mailer-daemon / postmaster
     * (NOT from the speaker), so the original recipient's email lives
     * inside the body. We extract every email-shaped token from each
     * Bounced row's body and combine those into one lowercase set.
     *
     * Returns: ['john@example.com' => true, ...]
     */
    public static function bouncedEmailsSet(): array
    {
        $set  = [];
        $skip = ['mailer-daemon', 'postmaster', 'noreply', 'no-reply', 'donotreply', 'do-not-reply', 'bounce@', 'abuse@'];

        self::where('category', 'Bounced')
            ->select('from_email', 'body_plain', 'subject')
            ->get()
            ->each(function ($r) use (&$set, $skip) {
                $candidates = [];

                // Sometimes the from_email IS the bounced recipient (rare)
                if ($r->from_email) {
                    $candidates[] = strtolower(trim($r->from_email));
                }

                // Pull every email-shaped token from subject + body
                $haystack = ($r->subject ?? '') . "\n" . ($r->body_plain ?? '');
                if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $haystack, $m)) {
                    foreach ($m[0] as $email) {
                        $candidates[] = strtolower(trim($email));
                    }
                }

                foreach ($candidates as $email) {
                    if ($email === '') continue;
                    $isSkip = false;
                    foreach ($skip as $s) {
                        if (str_contains($email, $s)) { $isSkip = true; break; }
                    }
                    if (!$isSkip) {
                        $set[$email] = true;
                    }
                }
            });

        return $set;
    }

    // Category → badge color
    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'Interested'     => '#16a34a',
            'Not Interested' => '#64748b',
            'Info Request'   => '#2563eb',
            'Out of Office'  => '#ca8a04',
            'Spam'           => '#dc2626',
            'Negative'       => '#9f1239',
            'No Reply'       => '#f97316',
            'Bounced'        => '#7c3aed',
            'Confirmed'      => '#0891b2',
            default          => '#94a3b8',
        };
    }

    public function getCategoryBgAttribute(): string
    {
        return match($this->category) {
            'Interested'     => '#f0fdf4',
            'Not Interested' => '#f8fafc',
            'Info Request'   => '#eff6ff',
            'Out of Office'  => '#fefce8',
            'Spam'           => '#fef2f2',
            'Negative'       => '#fff1f2',
            'No Reply'       => '#fff7ed',
            'Bounced'        => '#f5f3ff',
            'Confirmed'      => '#ecfeff',
            default          => '#f1f5f9',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'Interested'     => '🟢',
            'Not Interested' => '⚫',
            'Info Request'   => '🔵',
            'Out of Office'  => '🟡',
            'Spam'           => '🔴',
            'Negative'       => '🚫',
            'No Reply'       => '🟠',
            'Bounced'        => '↩️',
            'Confirmed'      => '✅',
            default          => '⚪',
        };
    }
}
