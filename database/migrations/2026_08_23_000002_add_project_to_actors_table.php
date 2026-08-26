<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->enum('project', ['ruta_d', 'eje'])
                ->nullable()
                ->after('linkage_status');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn('project');
        });
    }
};