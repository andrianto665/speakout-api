<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'file_path',
        'verification_code',
        'issued_at',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];
    
    protected $casts = [
        'issued_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
    
    // ============================================
    // 📌 CONSTANTS: Status Certificate
    // ============================================
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    // ============================================
    // 🔗 RELATIONSHIPS
    // ============================================
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    // ============================================
    // ✅ HELPER METHODS: Cek Status
    // ============================================
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
    
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
    
    // ============================================
    // 🔍 SCOPES: Filter by Status
    // ============================================
    
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
    
    // ============================================
    // ⚡ ACTION METHODS: Approve & Reject
    // ============================================
    
    /**
     * Approve certificate (by admin)
     * 
     * @param int $adminId - ID admin yang approve
     * @return bool
     */
    public function approve(int $adminId): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'rejection_reason' => null, // Clear rejection reason jika ada
        ]);
    }
    
    /**
     * Reject certificate (by admin)
     * 
     * @param int $adminId - ID admin yang reject
     * @param string $reason - Alasan penolakan
     * @return bool
     */
    public function reject(int $adminId, string $reason = ''): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
    
    // ============================================
    // 🔢 UTILITY: Generate Certificate Number & Code
    // ============================================
    
    /**
     * Generate unique certificate number: CERT-YYYY-XXXXX
     */
    public static function generateCertificateNumber(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('CERT-%s-%05d', $year, $count);
    }
    
    /**
     * Generate random 32-char verification code
     */
    public static function generateVerificationCode(): string
    {
        return bin2hex(random_bytes(16));
    }
    
    // ============================================
    // 🔍 UTILITY: Cek apakah user sudah punya certificate untuk course
    // ============================================
    
    /**
     * Cek apakah user sudah punya certificate untuk course tertentu
     * 
     * @param int $userId
     * @param int $courseId
     * @return bool
     */
    public static function existsForUserAndCourse(int $userId, int $courseId): bool
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->exists();
    }
    
    /**
     * Get atau create certificate untuk user+course
     * (Mencegah duplikat dengan unique constraint)
     * 
     * @param int $userId
     * @param int $courseId
     * @return Certificate
     */
    public static function getOrCreate(int $userId, int $courseId): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            [
                'certificate_number' => static::generateCertificateNumber(),
                'file_path' => 'generated_on_demand.pdf',
                'verification_code' => static::generateVerificationCode(),
                'status' => self::STATUS_PENDING, // Default status: pending
                'issued_at' => now(),
            ]
        );
    }
}