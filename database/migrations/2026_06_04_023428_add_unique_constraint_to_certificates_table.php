<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah unique constraint: 1 user hanya boleh punya 1 certificate per course
     * Ini mencegah duplikat certificate di masa depan
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Unique index: kombinasi user_id + course_id harus unik
            $table->unique(['user_id', 'course_id'], 'certificates_user_course_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropUnique('certificates_user_course_unique');
        });
    }
};