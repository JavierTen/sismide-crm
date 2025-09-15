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
        Schema::create('characterizations', function (Blueprint $table) {
            $table->id();
            // Relations
            $table->unsignedBigInteger('entrepreneur_id')->index()->comment('Reference to entrepreneurs.id');
            $table->unsignedBigInteger('economic_activity_id')->nullable()->index()->comment('Reference to economic_activities.id');
            $table->unsignedBigInteger('population_id')->nullable()->index()->comment('Reference to populations.id');

            // Enums and fields
            $table->enum('business_type', ['individual', 'associative'])->nullable()->comment('Tipo de negocio');
            $table->enum('business_age', ['over_6_months', 'over_12_months', 'over_24_months'])->nullable()->comment('Antigüedad del negocio');

            // Multi-selects stored as JSON
            $table->json('clients')->nullable()->comment('Clientela actual y potencial (json array)');
            $table->json('promotion_strategies')->nullable()->comment('Estrategias de promoción/ comunicación (json array)');

            // Average monthly sales enum
            $table->enum('average_monthly_sales', [
                'lt_500000',        // menos de 500.000
                '500k_1m',          // 501.000 hasta 1’000.000
                '1m_2m',            // 1’001.000 a 2’000.000
                '2m_5m',            // 2’001.000 a 5’000.000
                'gt_5m'             // más de 5’001.000
            ])->nullable()->comment('Promedio ventas mensuales');

            // Employees generated
            $table->enum('employees_generated', ['up_to_2', '3_to_4', 'more_than_5'])->nullable()->comment('Empleos generados');

            // Yes / No enums stored as booleans
            $table->boolean('has_accounting_records')->default(false)->comment('¿Lleva registro contable?');
            $table->boolean('has_commercial_registration')->default(false)->comment('¿Registro mercantil (Cámara de Comercio)?');
            $table->boolean('family_in_drummond')->default(false)->comment('Familiar hasta 3er grado en Drummond');

            // Georeference
            $table->string('georeference')->nullable()->comment('Georeferenciación (lat,lng o geojson)');

            // Evidence files (store path or url)
            $table->string('commerce_evidence_path')->nullable()->comment('Evidencia Cámara de Comercio (PDF/JPEG/PNG)');
            $table->string('population_evidence_path')->nullable()->comment('Evidencia Grupos Poblacionales (PDF/JPEG/PNG)');
            $table->string('photo_evidence_path')->nullable()->comment('Evidencia Fotográfica con Georreferenciación (JPEG/PNG)');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characterizations');
    }
};
