<?php
// app/Services/MeetingLockService.php

namespace App\Services;

use App\Models\Meeting;
use App\Models\UserProgress;

class MeetingLockService
{
    /**
     * Hasilkan [meeting_id => bool isLocked] untuk semua meeting dalam 1 course.
     */
    public static function getLockMap(int $userId, $meetings): array
    {
        $lessonMeetings = $meetings->filter(fn($m) => $m->isLesson())->values();

        $completedIds = UserProgress::where('user_id', $userId)
            ->where('is_completed', true)
            ->whereIn('meeting_id', $lessonMeetings->pluck('id'))
            ->pluck('meeting_id')
            ->toArray();

        $lockMap = [];
        $previousCompleted = true; // lesson pertama selalu terbuka

        foreach ($lessonMeetings as $lesson) {
            $lockMap[$lesson->id] = !$previousCompleted;
            $previousCompleted = in_array($lesson->id, $completedIds);
        }

        foreach ($meetings as $m) {
            if (!array_key_exists($m->id, $lockMap)) {
                $lockMap[$m->id] = false; // non-lesson selalu terbuka
            }
        }

        return $lockMap;
    }

    public static function isMeetingLocked(int $userId, Meeting $meeting): bool
    {
        $courseMeetings = Meeting::where('course_id', $meeting->course_id)
            ->orderBy('order_number', 'asc')
            ->get();

        return self::getLockMap($userId, $courseMeetings)[$meeting->id] ?? false;
    }
}