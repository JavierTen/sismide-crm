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
            // Add village_id column after ward_id
            $table->foreignId('village_id')
                ->nullable()
                ->after('ward_id')
                ->constrained('villages')
                ->onDelete('set null')
                ->comment('Village where business is located');

            // Add index for better query performance
            $table->index('village_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['village_id']);

            // Drop index
            $table->dropIndex(['village_id']);

            // Drop column
            $table->dropColumn('village_id');
        });
    }
};

