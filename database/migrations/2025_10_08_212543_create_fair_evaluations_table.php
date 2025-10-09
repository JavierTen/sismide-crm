<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fair_evaluations', function (Blueprint $table) {
            $table->id();

            // 1. Datos del Emprendimiento
            $table->foreignId('entrepreneur_id')->constrained('entrepreneurs')->cascadeOnDelete();

            // 2. Seleccionar Feria
            $table->foreignId('fair_id')->constrained('fairs')->cascadeOnDelete();

            // 3. Fecha de participación
            $table->date('participation_date');

            // 4. Experiencia en la Feria
            $table->enum('organization_rating', ['excellent', 'good', 'regular', 'poor']);
            $table->enum('visitor_flow', ['very_high', 'adequate', 'low']);
            $table->boolean('generated_contacts');
            $table->text('strategic_contacts_details')->nullable();

            // 5. Impacto en su Emprendimiento
            $table->enum('product_visibility', ['yes', 'partially', 'no']);
            $table->enum('total_sales', [
                'less_than_100k',
                'between_100k_200k',
                'between_200k_500k',
                'between_500k_1m',
                'more_than_1m'
            ]);
            $table->decimal('order_value', 12, 2)->default(0);
            $table->boolean('sufficient_products');
            $table->boolean('established_productive_chain');
            $table->text('productive_chain_details')->nullable();
            $table->text('observations')->nullable();

            // Manager que registró
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('entrepreneur_id');
            $table->index('fair_id');
            $table->index('participation_date');
            $table->index('manager_id');
            $table->index('deleted_at');

            // Índice único para evitar duplicados (un emprendedor solo puede evaluar una vez por feria)
            $table->unique(['entrepreneur_id', 'fair_id'], 'unique_entrepreneur_fair_evaluation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fair_evaluations');
    }
};
