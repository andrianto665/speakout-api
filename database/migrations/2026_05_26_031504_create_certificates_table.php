<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_create_certificates_table.php
public function up()
{
    Schema::create('certificates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('course_id')->constrained()->onDelete('cascade');
        $table->string('certificate_number', 50)->unique(); // Contoh: CERT-2026-001234
        $table->string('file_path'); // Path file PDF di storage
        $table->string('verification_code', 32)->unique(); // Untuk QR code
        $table->timestamp('issued_at')->useCurrent();
        $table->timestamps();
        
        $table->index(['user_id', 'course_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
