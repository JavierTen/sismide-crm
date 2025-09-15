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
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('economic_activity_id')
                ->nullable()
                ->after('productive_line_id')
                ->constrained('economic_activities')
                ->comment('Economic activity associated with the business');

            // Agregar índice para mejor performance
            $table->index('economic_activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['economic_activity_id']);
            $table->dropIndex(['economic_activity_id']);
            $table->dropColumn('economic_activity_id');
        });
    }
};
