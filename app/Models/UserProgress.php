<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    protected $fillable = ['user_id', 'meeting_id', 'is_completed', 'completed_at'];
    
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime'
    ];
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function meeting() {
        return $this->belongsTo(Meeting::class);
    }
}