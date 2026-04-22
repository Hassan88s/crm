<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'speaker_id', 'smtp_account_id', 'to_email', 'to_name',
        'subject', 'body', 'status', 'error',
    ];

    public function speaker()
    {
        return $this->belongsTo(Speaker::class);
    }

    public function smtpAccount()
    {
        return $this->belongsTo(SmtpAccount::class);
    }
}
