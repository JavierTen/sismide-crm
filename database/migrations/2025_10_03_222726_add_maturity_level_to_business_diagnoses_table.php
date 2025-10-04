<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_diagnoses', function (Blueprint $table) {
            $table->string('maturity_level', 100)->nullable()->after('total_score');
        });
    }

    public function down(): void
    {
        Schema::table('business_diagnoses', function (Blueprint $table) {
            $table->dropColumn('maturity_level');
        });
    }
};
