<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tambah kolom status, approved_by, approved_at, rejection_reason
     * ke tabel certificates untuk workflow approval
     */
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // 1. Kolom status: pending, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('verification_code');
            
            // 2. Kolom approved_by: ID admin yang approve (nullable)
            $table->foreignId('approved_by')
                  ->nullable()
                  ->after('status')
                  ->constrained('users')
                  ->nullOnDelete();
            
            // 3. Kolom approved_at: waktu approval (nullable)
            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');
            
            // 4. Kolom rejection_reason: alasan penolakan (nullable)
            $table->text('rejection_reason')
                  ->nullable()
                  ->after('approved_at');
            
            // 5. Index untuk query cepat by status
            $table->index('status');
        });
        
        // ✅ Update semua certificate yang sudah ada menjadi 'approved'
        // (karena sebelumnya tidak ada status, anggap semua sudah approved)
        DB::table('certificates')
            ->whereNull('status')
            ->update(['status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Hapus foreign key dulu
            $table->dropForeign(['approved_by']);
            
            // Hapus index
            $table->dropIndex(['status']);
            
            // Hapus kolom
            $table->dropColumn([
                'status',
                'approved_by',
                'approved_at',
                'rejection_reason'
            ]);
        });
    }
};