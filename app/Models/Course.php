<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'title',
        'description',
        'instructor',
        'price',
        'level',
        'thumbnail'
    ];

    // RELATIONSHIP
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
}