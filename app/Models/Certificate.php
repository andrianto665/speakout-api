<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class Certificate extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'certificate_number',
        'verification_code',
        'status',
        'issued_at',
        'approved_at',
        'approved_by',
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
    
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
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
            'rejection_reason' => null,
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
        return DB::transaction(function () use ($year) {
            $last = static::where('certificate_number', 'like', "CERT-{$year}-%")
                ->lockForUpdate()->orderByDesc('id')->first();
            $lastNumber = 0;
            if ($last && preg_match('/CERT-' . $year . '-(\d{5})/', $last->certificate_number, $m)) {
                $lastNumber = (int) $m[1];
            }
            return sprintf('CERT-%s-%05d', $year, $lastNumber + 1);
        });
    }
    
    /**
     * Generate random verification code (UUID format)
     */
    public static function generateVerificationCode(): string
    {
        return strtoupper(bin2hex(random_bytes(6))); // 6 bytes = 12 hex chars
    }

    // ============================================
    // 🔍 UTILITY: Query Helpers
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
     * Get certificate by user and course
     * 
     * @param int $userId
     * @param int $courseId
     * @return Certificate|null
     */
    public static function getByUserAndCourse(int $userId, int $courseId): ?self
    {
        return static::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->first();
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
                'verification_code' => static::generateVerificationCode(),
                'status' => self::STATUS_PENDING,
                'issued_at' => now(),
            ]
        );
    }
    
    /**
     * Get approved certificates count for user
     * 
     * @param int $userId
     * @return int
     */
    public static function getApprovedCountForUser(int $userId): int
    {
        return static::where('user_id', $userId)
            ->approved()
            ->count();
    }
    
    /**
     * Get statistics summary
     * 
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total' => static::count(),
            'pending' => static::pending()->count(),
            'approved' => static::approved()->count(),
            'rejected' => static::rejected()->count(),
        ];
    }

    // ============================================
    // 🔄 BOOT METHOD: Auto-generate saat creating
    // ============================================
    
    protected static function boot()
    {
        parent::boot();
        
        // Auto-generate certificate number & verification code saat creating
        static::creating(function ($certificate) {
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = static::generateCertificateNumber();
            }
            
            if (empty($certificate->verification_code)) {
                $certificate->verification_code = static::generateVerificationCode();
            }
        });
    }
}