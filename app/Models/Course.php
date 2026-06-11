<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'instructor',
        'thumbnail',
        'category',
        'level',
        'price',
        'duration',
    ];

    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}