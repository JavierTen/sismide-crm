<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE actors MODIFY contact_name VARCHAR(255) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE actors MODIFY contact_role VARCHAR(255) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE actors MODIFY contact_email VARCHAR(255) NULL DEFAULT NULL');
        DB::statement('ALTER TABLE actors MODIFY contact_phone VARCHAR(50) NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE actors MODIFY contact_name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE actors MODIFY contact_role VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE actors MODIFY contact_email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE actors MODIFY contact_phone VARCHAR(50) NOT NULL');
    }
};
