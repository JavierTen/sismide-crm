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
        Schema::create('business_diagnoses', function (Blueprint $table) {
            $table->id();

            // Relación con emprendedor
            $table->foreignId('entrepreneur_id')
                ->constrained('entrepreneurs')
                ->onDelete('cascade')
                ->comment('Reference to entrepreneur');

            // Información básica del diagnóstico
            $table->date('diagnosis_date')->comment('Fecha del diagnóstico');
            // Novedades del emprendimiento
            $table->boolean('has_news')->default(false)->comment('¿El emprendimiento registra novedades?');
            $table->enum('news_type', [
                'reactivation',
                'definitive_closure',
                'temporary_closure',
                'permanent_disability',
                'temporary_disability',
                'definitive_retirement',
                'temporary_retirement',
                'address_change',
                'owner_death',
                'no_news'
            ])->nullable()->comment('Tipo de novedad');
            $table->date('news_date')->nullable()->comment('Fecha de la novedad');

            // Secciones de la encuesta (JSON)
            $table->json('administrative_section')->nullable()->comment('Respuestas sección administrativa');
            $table->json('financial_section')->nullable()->comment('Respuestas sección financiera');
            $table->json('production_section')->nullable()->comment('Respuestas sección de producción');
            $table->json('market_section')->nullable()->comment('Respuestas sección de mercado');
            $table->json('technology_section')->nullable()->comment('Respuestas sección tecnológica');

            // Observaciones y plan de trabajo
            $table->text('general_observations')->nullable()->comment('Observaciones generales y mini DOFA');
            $table->json('work_sections')->nullable()->comment('Secciones seleccionadas para trabajar (mínimo 2)');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_diagnoses');
    }
};
