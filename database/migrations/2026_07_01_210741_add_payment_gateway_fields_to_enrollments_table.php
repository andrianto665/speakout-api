<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('payment_gateway_order_id')->nullable()->after('payment_status');
            $table->string('payment_gateway_snap_token')->nullable()->after('payment_gateway_order_id');
            $table->string('payment_gateway_status')->nullable()->after('payment_gateway_snap_token');
            $table->text('payment_gateway_response')->nullable()->after('payment_gateway_status');
            // paid_at tidak ditambahkan lagi karena sudah ada di tabel
        });
    }

    public function down()
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway_order_id',
                'payment_gateway_snap_token',
                'payment_gateway_status',
                'payment_gateway_response',
            ]);
        });
    }
};