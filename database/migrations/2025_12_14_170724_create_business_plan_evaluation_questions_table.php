<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_plan_evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->integer('question_number')->comment('Número de pregunta (1-11)');
            $table->string('question_text', 500)->comment('Texto de la pregunta');
            $table->text('description')->nullable()->comment('Descripción detallada');
            $table->enum('target_role', ['evaluator', 'manager'])->comment('A quién va dirigida');
            $table->decimal('weight', 4, 2)->comment('Ponderación (0.05 = 5%)');
            $table->integer('order')->default(0)->comment('Orden de visualización');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // ✅ Constraint único combinado: question_number + target_role
            $table->unique(['question_number', 'target_role'], 'unique_question_per_role');

            // Índices
            $table->index('target_role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_plan_evaluation_questions');
    }
};
