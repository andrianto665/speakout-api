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
    ];
    
    protected $casts = [
        'issued_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
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
}