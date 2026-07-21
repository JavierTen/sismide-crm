<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_evaluations', function (Blueprint $table) {
            $table->string('technical_verdict')->nullable()->after('technical_concept');
            $table->text('technical_conditions')->nullable()->after('technical_verdict');
        });
    }

    public function down(): void
    {
        Schema::table('institution_evaluations', function (Blueprint $table) {
            $table->dropColumn(['technical_verdict', 'technical_conditions']);
        });
    }
};
