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
        DB::statement("ALTER TABLE characterizations MODIFY COLUMN business_age ENUM('lt_6_months','over_6_months','over_12_months','over_24_months') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE characterizations MODIFY COLUMN business_age ENUM('over_6_months','over_12_months','over_24_months') NULL");
    }
};
