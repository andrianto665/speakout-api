<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    // ✅ TAMBAHKAN SEMUA KOLOM YANG MAU DI-INSERT
    protected $fillable = [
        'course_id',
        'title',          // ← Wajib! (karena kita pakai 'title', bukan 'name')
        'order_number',   // ← Wajib! (karena tabel pakai 'order_number')
        'content',
        'type',
        'has_test',
        'is_final_test'
    ];

    // Relasi ke Course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // 🔗 Relasi ke Kuis (Opsional, karena tidak semua meeting adalah kuis)
    public function quiz(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Quiz::class, 'meeting_id');
    }
}