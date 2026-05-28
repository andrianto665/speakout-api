<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update ENUM type untuk kolom 'type' di tabel meetings
        // Tambah 'quiz' ke daftar nilai yang diizinkan
        DB::statement("ALTER TABLE meetings MODIFY COLUMN type ENUM('normal', 'test', 'final', 'quiz') NOT NULL DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: hapus 'quiz' dari ENUM (hati-hati: pastikan tidak ada data dengan type='quiz')
        DB::statement("ALTER TABLE meetings MODIFY COLUMN type ENUM('normal', 'test', 'final') NOT NULL DEFAULT 'normal'");
    }
};