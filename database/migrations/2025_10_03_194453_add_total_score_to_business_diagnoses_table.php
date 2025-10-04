<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_diagnoses', function (Blueprint $table) {
            $table->integer('total_score')->nullable()->after('work_sections');
        });
    }

    public function down(): void
    {
        Schema::table('business_diagnoses', function (Blueprint $table) {
            $table->dropColumn('total_score');
        });
    }
};
