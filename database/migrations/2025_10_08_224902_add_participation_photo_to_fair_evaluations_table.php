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
        Schema::table('fair_evaluations', function (Blueprint $table) {
            $table->string('participation_photo_path')->nullable()->after('participation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fair_evaluations', function (Blueprint $table) {
            $table->dropColumn('participation_photo_path');
        });
    }
};
