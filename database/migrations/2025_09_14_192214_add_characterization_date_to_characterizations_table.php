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
            $table->date('characterization_date')
                ->nullable()
                ->after('photo_evidence_path')
                ->comment('Date of characterization');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('characterizations', function (Blueprint $table) {
            $table->dropColumn('characterization_date');
        });
    }
};
