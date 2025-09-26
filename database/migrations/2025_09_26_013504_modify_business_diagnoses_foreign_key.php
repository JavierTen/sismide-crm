<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_diagnoses', function (Blueprint $table) {
            // Eliminar constraint existente
            $table->dropForeign(['entrepreneur_id']);

            // Recrear la foreign key con cascade (mantener para hard delete)
            $table->foreign('entrepreneur_id')
                  ->references('id')
                  ->on('entrepreneurs')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Rollback si es necesario
    }
};
