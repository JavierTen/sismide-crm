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
            // Eliminar el campo georeference
            $table->dropColumn('georeference');

            // Agregar campos de latitud y longitud
            $table->decimal('latitude', 10, 8)->nullable()->comment('Latitud de la georeferenciación');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Longitud de la georeferenciación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characterizations', function (Blueprint $table) {
            // Eliminar los campos de latitud y longitud
            $table->dropColumn(['latitude', 'longitude']);

            // Restaurar el campo georeference
            $table->string('georeference')->nullable()->comment('Georeferenciación (lat,lng o geojson)');
        });
    }
};
