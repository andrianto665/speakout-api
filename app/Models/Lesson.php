<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'meeting_id', 'title', 'type', 'google_drive_link', 'order'
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}