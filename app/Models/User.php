<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role', // ← Tambahkan ini
        'google_id',  // ✅ TAMBAHKAN
        'avatar',     // ✅ TAMBAHKAN
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // app/Models/User.php - tambahkan relasi:
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
    return $this->hasMany(Enrollment::class);
    }

    public function enrolledCourses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
    return $this->belongsToMany(Course::class, 'enrollments')
                ->withTimestamps()
                ->withPivot('enrolled_at', 'completed_at');
    }
    public function certificates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
    return $this->hasMany(Certificate::class);
    }
    public function quizAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
    return $this->hasMany(QuizAttempt::class);
    }
    public function progress(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
    return $this->hasMany(UserProgress::class);
    }
}
