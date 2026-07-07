<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE businesses MODIFY COLUMN cohort ENUM('1','2','3','4','5','6','nuevo') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE businesses MODIFY COLUMN cohort ENUM('1','2','3','4','5','6') NULL");
    }
};
