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
        Schema::table('business_diagnoses', function (Blueprint $table) {
            $table->enum('diagnosis_type', ['entry', 'exit'])
                ->default('entry')
                ->after('entrepreneur_id')
                ->comment('Tipo de diagnóstico: entrada o salida');
        });

        // Actualizar todos los registros existentes a 'entry'
        DB::table('business_diagnoses')->update(['diagnosis_type' => 'entry']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_diagnoses', function (Blueprint $table) {
            $table->dropColumn('diagnosis_type');
        });
    }
};