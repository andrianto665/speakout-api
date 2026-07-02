<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE enrollments MODIFY payment_method ENUM('dana','gopay','bank_transfer','qris','midtrans') NULL");
    }

    public function down()
    {
        DB::statement("ALTER TABLE enrollments MODIFY payment_method ENUM('dana','gopay','bank_transfer','qris') NULL");
    }
};