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
        Schema::table('enrollments', function (Blueprint $table) {
            // ✅ KOLOM PEMBAYARAN
            $table->enum('payment_status', ['pending', 'paid', 'rejected'])
                  ->default('pending')
                  ->after('course_id');
            
            $table->string('payment_proof')->nullable()->after('payment_status');
            
            $table->enum('payment_method', ['dana', 'gopay', 'qris', 'bank_transfer'])
                  ->nullable()
                  ->after('payment_proof');
            
            $table->decimal('amount_paid', 10, 2)->nullable()->after('payment_method');
            
            $table->timestamp('paid_at')->nullable()->after('amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_proof',
                'payment_method',
                'amount_paid',
                'paid_at',
            ]);
        });
    }
};