<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id', 'speaker_id', 'status', 'scheduled_at',
        'ai_topic', 'generated_subject', 'generated_body',
        'smtp_account_id', 'error', 'sent_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at'      => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function speaker()
    {
        return $this->belongsTo(Speaker::class);
    }

    public function smtpAccount()
    {
        return $this->belongsTo(SmtpAccount::class);
    }
}
