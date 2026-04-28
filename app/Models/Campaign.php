<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'subject_template', 'body_template', 'signature_template',
        'agenda_pdf_path', 'agenda_filename', 'openai_file_id',
        'event_id', 'throttle_seconds', 'attach_agenda',
        'status', 'total_count', 'sent_count', 'failed_count',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'attach_agenda' => 'boolean',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeRunning($q)  { return $q->where('status', 'running'); }
    public function scopeRunnable($q) { return $q->whereIn('status', ['running']); }

    public function progressPercent(): int
    {
        if ($this->total_count <= 0) return 0;
        $done = $this->sent_count + $this->failed_count;
        return (int) round(($done / $this->total_count) * 100);
    }
}
