<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\Certificate;
use App\Models\UserProgress;
use App\Services\CertificateService;

class GenerateMissingCertificates extends Command
{
    protected $signature = 'certificates:generate-missing 
                            {--user= : Generate for specific user ID} 
                            {--course= : Generate for specific course ID}
                            {--dry-run : Show what would be generated without actually creating}';
    
    protected $description = 'Generate certificates for users who completed courses but missing certificate records';

    public function handle(CertificateService $certificateService)
    {
        $this->info('🔍 Searching for missing certificates...');
        
        // Get target users
        $userId = $this->option('user');
        $users = $userId ? User::find([$userId]) : User::where('role', 'user')->get();
        
        $generated = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            $this->line("👤 Checking user: {$user->name} (ID: {$user->id})");
            
            // Get all courses this user is enrolled in
            $enrolledCourseIds = $user->enrolledCourses()->pluck('courses.id');
            
            foreach ($enrolledCourseIds as $courseId) {
                // Skip if specific course filter is set
                if ($this->option('course') && $this->option('course') != $courseId) {
                    continue;
                }
                
                // Skip if certificate already exists
                if (Certificate::where('user_id', $user->id)->where('course_id', $courseId)->exists()) {
                    $this->line("   ✅ Certificate already exists for course ID {$courseId}");
                    $skipped++;
                    continue;
                }
                
                // Check if all lessons are completed
                $course = Course::find($courseId);
                $lessonIds = $course->meetings()->whereNotNull('content')->pluck('id');
                
                if ($lessonIds->isEmpty()) {
                    $this->line("   ⚠️  No lessons found for course ID {$courseId}");
                    continue;
                }
                
                $completedCount = UserProgress::where('user_id', $user->id)
                    ->whereIn('meeting_id', $lessonIds)
                    ->where('is_completed', true)
                    ->count();
                
                $totalLessons = $lessonIds->count();
                
                if ($completedCount >= $totalLessons) {
                    // All lessons completed → generate certificate
                    if ($this->option('dry-run')) {
                        $this->line("   🎯 WOULD GENERATE: {$course->title} for {$user->name}");
                        $generated++;
                    } else {
                        try {
                            $cert = $certificateService->generateCertificate($user, $course);
                            $this->line("   ✅ Generated: {$cert->certificate_number} for {$course->title}");
                            $generated++;
                        } catch (\Exception $e) {
                            $this->error("   ❌ Failed: {$e->getMessage()}");
                        }
                    }
                } else {
                    $this->line("   ⏳ Not completed yet: {$completedCount}/{$totalLessons} lessons");
                }
            }
        }
        
        $this->newLine();
        $this->info("📊 Summary: {$generated} generated, {$skipped} skipped");
        
        if ($this->option('dry-run')) {
            $this->warn('💡 This was a dry run. Remove --dry-run to actually generate certificates.');
        }
        
        return Command::SUCCESS;
    }
}