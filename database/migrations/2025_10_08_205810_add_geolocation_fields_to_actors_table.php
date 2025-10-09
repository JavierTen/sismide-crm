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
            $table->decimal('latitude', 10, 8)->nullable()->after('office_hours');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('georeference_photo_path')->nullable()->after('longitude');

            // Índice para búsquedas geográficas
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude', 'georeference_photo_path']);
        });
    }
};
