<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrar registros con cohorte 6 a 'nuevo'
        DB::table('businesses')->where('cohort', '6')->update(['cohort' => 'nuevo']);

        // Eliminar '6' del ENUM
        DB::statement("ALTER TABLE businesses MODIFY COLUMN cohort ENUM('1','2','3','4','5','nuevo') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE businesses MODIFY COLUMN cohort ENUM('1','2','3','4','5','6','nuevo') NULL");
    }
};
