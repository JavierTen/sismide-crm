<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_plan_evaluations', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('business_plan_id')
                ->constrained('business_plans')
                ->onDelete('cascade');

            $table->foreignId('evaluator_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Usuario que evalúa (Evaluador o Gestor)');

            $table->foreignId('question_id')
                ->constrained('business_plan_evaluation_questions')
                ->onDelete('cascade');

            // Datos de la evaluación
            $table->enum('evaluator_type', ['evaluator', 'manager'])
                ->comment('Tipo de evaluador');

            $table->integer('question_number')
                ->comment('Número de pregunta (1-11)');

            $table->decimal('score', 4, 2)
                ->comment('Calificación (1-10)');

            $table->text('comments')->nullable()
                ->comment('Recomendaciones del evaluador');

            $table->timestamps();
            $table->softDeletes();

            // Constraint único: Un evaluador solo puede calificar una vez cada pregunta por plan
            $table->unique(
                ['business_plan_id', 'evaluator_id', 'question_number'],
                'unique_evaluation_per_question'
            );

            // Índices para optimización
            $table->index('business_plan_id');
            $table->index('evaluator_id');
            $table->index('evaluator_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_plan_evaluations');
    }
};
