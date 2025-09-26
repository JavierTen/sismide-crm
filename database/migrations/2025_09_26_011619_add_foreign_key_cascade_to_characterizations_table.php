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
        Schema::table('characterizations', function (Blueprint $table) {
            // Agregar foreign key para entrepreneur_id con cascade delete
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
        Schema::table('characterizations', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['entrepreneur_id']);
        });
    }
};
