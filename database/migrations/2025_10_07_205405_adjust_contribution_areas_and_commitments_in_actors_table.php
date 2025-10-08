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
        Schema::table('actors', function (Blueprint $table) {
            // Contribution_areas debe ser JSON (CheckboxList - múltiple selección)
            $table->json('contribution_areas')->nullable()->change();

            // Commitments debe ser STRING (Radio - única selección)
            $table->string('commitments')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            // Revertir cambios
            $table->string('contribution_areas')->nullable()->change();
            $table->json('commitments')->nullable()->change();
        });
    }
};
