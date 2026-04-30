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
            default          => '⚪',
        };
    }
}
