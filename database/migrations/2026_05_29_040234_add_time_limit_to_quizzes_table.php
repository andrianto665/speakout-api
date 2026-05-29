<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Tambah kolom time_limit (dalam menit), default 60 menit (1 jam)
            $table->integer('time_limit')->default(60)->after('passing_score');
            // 60 menit = 1 jam
            // Bisa diubah jadi 30, 90, 120, dll sesuai kebutuhan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('time_limit');
        });
    }
};