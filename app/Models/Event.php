<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'location',
        'date',
        'end_date',
        'time',
        'image',
        'description',
        'status',
    ];

    protected $casts = [
        'date'     => 'date',
        'end_date' => 'date',
    ];

    public function speakers()
    {
        return $this->hasMany(Speaker::class);
    }
}
