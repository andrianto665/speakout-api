<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $instructor
 * @property string|null $thumbnail
 * @property string|null $category
 * @property string|null $level
 * @property float|null $price
 * @property string|null $duration
 */
class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'instructor',
        'instructor_id',
        'thumbnail',
        'category',
        'level',
        'price',
        'duration',
    ];

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function instructorUser(): BelongsTo
    {
    return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}