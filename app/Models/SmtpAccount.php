<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpAccount extends Model
{
    protected $fillable = [
        'name', 'host', 'port', 'username', 'password',
        'encryption', 'from_address', 'from_name',
        'is_active', 'last_used_at', 'emails_sent',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the next active SMTP account for round-robin rotation.
     * Returns the active account with the oldest (or null) last_used_at.
     * Increments emails_sent and updates last_used_at atomically.
     * Returns null if no active accounts exist.
     */
    public static function nextForRotation(): ?self
    {
        $account = self::active()
            ->orderByRaw('last_used_at IS NULL DESC, last_used_at ASC')
            ->first();

        if (!$account) {
            return null;
        }

        $account->update([
            'last_used_at' => now(),
            'emails_sent'  => $account->emails_sent + 1,
        ]);

        return $account;
    }
}
