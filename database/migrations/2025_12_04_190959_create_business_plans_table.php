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
        Schema::create('business_plans', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('entrepreneur_id')->constrained('entrepreneurs')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');

            // Fecha de creación del plan
            $table->date('creation_date')->nullable();

            // Información del negocio
            $table->text('business_definition')->nullable();
            $table->text('problems_to_solve')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();

            // Capitalización
            $table->boolean('is_capitalized')->default(false);
            $table->year('capitalization_year')->nullable();

            // Propuesta y requerimientos
            $table->text('value_proposition')->nullable();
            $table->longText('requirements_needs')->nullable();

            // Métricas financieras - Ventas
            $table->decimal('monthly_sales_cop', 15, 2)->nullable()->comment('Volumen de ventas mensual (COP)');
            $table->integer('monthly_sales_units')->nullable()->comment('Volumen de ventas mensual (Unidades)');
            $table->string('production_frequency')->nullable()->comment('Frecuencia de producción');

            // Métricas financieras - Rentabilidad
            $table->decimal('gross_profitability_rate', 5, 2)->nullable()->comment('Tasa de rentabilidad bruta (%)');
            $table->decimal('cash_flow_growth_rate', 5, 2)->nullable()->comment('Tasa de crecimiento proyectada del flujo de caja (%)');
            $table->decimal('internal_return_rate', 5, 2)->nullable()->comment('Tasa Interna de Retorno (%)');

            // Punto de equilibrio
            $table->integer('break_even_units')->nullable()->comment('Punto de equilibrio (Unidades)');
            $table->decimal('break_even_cop', 15, 2)->nullable()->comment('Punto de equilibrio (COP)');

            // Inversión y empleos
            $table->decimal('current_investment_value', 15, 2)->nullable()->comment('Valor de la inversión actual (COP)');
            $table->integer('jobs_generated')->nullable()->comment('Número de empleos generados');

            // Mercado
            $table->text('direct_competitors')->nullable()->comment('Competidores directos');
            $table->text('target_market')->nullable()->comment('Mercado objetivo');

            // Otros
            $table->text('observations')->nullable();

            // Archivo adjunto
            $table->string('business_plan_path')->nullable()->comment('Ruta del archivo del plan de negocio');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_plans');
    }
};
