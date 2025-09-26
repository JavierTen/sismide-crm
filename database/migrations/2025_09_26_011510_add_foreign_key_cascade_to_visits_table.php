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
        Schema::table('visits', function (Blueprint $table) {
            // Agregar la foreign key con cascade delete
            $table->foreign('entrepreneur_id')
                  ->references('id')
                  ->on('entrepreneurs')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['entrepreneur_id']);
        });
    }
};
