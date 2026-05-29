<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom 'role' jadi VARCHAR(20) agar bisa terima 'student', 'admin', dll
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('student')->change();
        });
        
        // Update data lama yang mungkin kosong atau invalid
        DB::table('users')->whereNull('role')->orWhere('role', '')->update(['role' => 'student']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // (Opsional) Kembalikan ke definisi lama jika perlu rollback
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 5)->default('admin')->change();
        });
    }
};