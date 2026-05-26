<?php
/**
 * Enrollment Model - Represents a user's enrollment in a course
 * 
 * This model tracks when a user enrolls in a course, their progress,
 * and completion status.
 * 
 * @package App\Models
 * @author SpeakOut Team
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Enrollment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'course_id',
        'enrolled_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // Add fields to hide if needed
    ];

    /**
     * Boot the model and add global scopes if needed.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set enrolled_at when creating new enrollment
        static::creating(function ($enrollment) {
            if (!$enrollment->enrolled_at) {
                $enrollment->enrolled_at = now();
            }
        });
    }

    // =========================================================================
    // 🔗 RELATIONSHIPS
    // =========================================================================

    /**
     * Get the user who enrolled in the course.
     *
     * @return BelongsTo<User, Enrollment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course that the user enrolled in.
     *
     * @return BelongsTo<Course, Enrollment>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    // =========================================================================
    // 🔍 SCOPES (Query Builders)
    // =========================================================================

    /**
     * Scope a query to only include active (not completed) enrollments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Scope a query to only include completed enrollments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope a query to only include enrollments for a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include enrollments for a specific course.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $courseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // =========================================================================
    // ✨ ACCESSORS & MUTATORS
    // =========================================================================

    /**
     * Check if the enrollment is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if the enrollment is still active (in progress).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return !$this->isCompleted();
    }

    /**
     * Mark this enrollment as completed.
     *
     * @return $this
     */
    public function markAsCompleted()
    {
        $this->update(['completed_at' => now()]);
        return $this;
    }

    /**
     * Get the enrollment duration in days.
     *
     * @return int|null
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->enrolled_at) {
            return null;
        }

        $end = $this->completed_at ?? Carbon::now();
        return $this->enrolled_at->diffInDays($end);
    }

    // =========================================================================
    // 📦 SERIALIZATION (Optional: Customize API response)
    // =========================================================================

    /**
     * Append additional attributes to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'status',
        'duration_days',
    ];

    /**
     * Get the enrollment status attribute.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        return $this->isCompleted() ? 'completed' : 'in_progress';
    }

    /**
     * Get the duration in days attribute.
     *
     * @return int|null
     */
    public function getDurationDaysAttribute(): ?int
    {
        return $this->getDurationInDays();
    }
}