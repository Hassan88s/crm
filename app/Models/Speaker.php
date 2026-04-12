<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Speaker extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'title', 'company',
        'email', 'seniority', 'country', 'photo', 'event_id',
    ];

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
