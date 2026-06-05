<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'title',
        'description',
        'instructor',
        'category',
        'level',
        'price',        // ✅ PASTIKAN ADA
        'duration',     // ✅ PASTIKAN ADA
        'thumbnail',
        'status',
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

    // app/Models/Course.php - tambahkan relasi:
public function enrollments() {
    return $this->hasMany(Enrollment::class);
}

public function enrolledUsers() {
    return $this->belongsToMany(User::class, 'enrollments')
                ->withTimestamps()
                ->withPivot('enrolled_at', 'completed_at');
}
}