<?php
/**
 * Enrollment Model - Represents a user's enrollment in a course
 * 
 * This model tracks when a user enrolls in a course, their progress,
 * completion status, and payment information.
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
        'is_completed',
        // ✅ KOLOM BARU UNTUK PEMBAYARAN
        'payment_status',
        'payment_proof',
        'payment_method',
        'amount_paid',
        'paid_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'is_completed' => 'boolean',
        'amount_paid' => 'decimal:2',
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
            
            // Set default payment status
            if (!$enrollment->payment_status) {
                $enrollment->payment_status = 'pending';
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

    /**
     * Scope a query to only include paid enrollments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope a query to only include pending payment enrollments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingPayment($query)
    {
        return $query->where('payment_status', 'pending');
    }

    // =========================================================================
    // ✨ ACCESSORS & MUTATORS - EXISTING
    // =========================================================================

    /**
     * Check if the enrollment is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null || $this->is_completed === true;
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
    // 💳 PAYMENT METHODS - NEW
    // =========================================================================

    /**
     * Check if payment is completed.
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if payment is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Check if payment is rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->payment_status === 'rejected';
    }

    /**
     * Check if enrollment can access course content.
     * User can only access if payment is approved.
     *
     * @return bool
     */
    public function canAccessCourse(): bool
    {
        return $this->isPaid();
    }

    /**
     * Approve payment for this enrollment.
     *
     * @return bool
     */
    public function approvePayment(): bool
    {
        return $this->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Reject payment for this enrollment.
     *
     * @param string $reason Optional rejection reason
     * @return bool
     */
    public function rejectPayment(string $reason = ''): bool
    {
        return $this->update([
            'payment_status' => 'rejected',
            // Jika nanti ada kolom rejection_reason, bisa ditambahkan di sini
        ]);
    }

    /**
     * Mark payment as pending (for re-submission).
     *
     * @return bool
     */
    public function markPaymentPending(): bool
    {
        return $this->update([
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);
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
        'payment_status_label',
        'can_access',
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

    /**
     * Get the payment status label for API response.
     *
     * @return string
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Lunas',
            'rejected' => 'Ditolak',
        ];

        return $labels[$this->payment_status] ?? 'Unknown';
    }

    /**
     * Get the can access attribute for API response.
     *
     * @return bool
     */
    public function getCanAccessAttribute(): bool
    {
        return $this->canAccessCourse();
    }

    /**
     * Get the array representation of the enrollment.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        
        // Add payment information to array
        $array['payment'] = [
            'status' => $this->payment_status,
            'status_label' => $this->payment_status_label,
            'amount' => $this->amount_paid,
            'method' => $this->payment_method,
            'paid_at' => $this->paid_at,
            'can_access' => $this->canAccessCourse(),
        ];

        return $array;
    }
}