<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('enrollments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('course_id')->constrained()->onDelete('cascade');
        $table->timestamp('enrolled_at')->useCurrent();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
        
        // Unique: 1 user hanya bisa enroll 1 course sekali
        $table->unique(['user_id', 'course_id']);
    });
}

public function down()
{
    Schema::dropIfExists('enrollments');
}
};
