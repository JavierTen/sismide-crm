<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educational_institution_id')->constrained('educational_institutions')->onDelete('cascade');

            $table->json('pedagogical_section')->nullable();
            $table->json('sustainability_section')->nullable();
            $table->json('entrepreneurial_culture_section')->nullable();
            $table->json('territorial_impact_section')->nullable();
            $table->json('operational_capacity_section')->nullable();
            $table->longText('technical_concept')->nullable();

            $table->unsignedInteger('total_score')->nullable();
            $table->string('result_category')->nullable();

            $table->foreignId('manager_id')->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_evaluations');
    }
};
