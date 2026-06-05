<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Hanya tambahkan kolom price jika belum ada
            if (!Schema::hasColumn('courses', 'price')) {
                $table->integer('price')->default(0)->after('level');
            }
            
            // Hanya tambahkan kolom duration jika belum ada
            if (!Schema::hasColumn('courses', 'duration')) {
                $table->string('duration')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('courses', 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};