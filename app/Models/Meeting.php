<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $course_id
 * @property string $title
 * @property int $order_number
 * @property string|null $content
 * @property string $type
 * @property int $has_test
 * @property int $is_final_test
 */
class Meeting extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'order_number',
        'content',
        'type',
        'has_test',
        'is_final_test'
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'meeting_id');
    }

    public function isLesson(): bool
    {
        if ($this->content !== null && $this->content !== '') {
            return true;
        }
        $type = strtolower($this->type ?? '');
        if (in_array($type, ['quiz', 'final', 'test', 'quiz_assessment', 'assessment'])) {
            return true;
        }
        if (!empty($this->has_test) || !empty($this->is_final_test)) {
            return true;
        }
        return false;
    }
}