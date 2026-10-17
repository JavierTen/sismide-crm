<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Formato de evaluación por año.
 *
 * Las preguntas actuales quedan como el formato 2025, sin ningún cambio, y se
 * crea el formato 2026: nuevas ponderaciones, "Ubicación física del
 * emprendimiento" como primer factor del criterio global y sin el criterio
 * técnico del gestor. Las calificaciones ya guardadas apuntan a sus preguntas,
 * así que los resultados de 2025 no se alteran.
 */
return new class extends Migration
{
    private const FORMAT_2026 = 2026;

    /** Ponderaciones del formato 2026, por número de pregunta (suman 100 %). */
    private const WEIGHTS_2026 = [
        1 => 0.10, 2 => 0.05, 3 => 0.10, 4 => 0.05, 5 => 0.10, 6 => 0.05,
        7 => 0.05, 8 => 0.10, 9 => 0.10, 10 => 0.10, 11 => 0.20,
    ];

    public function up(): void
    {
        Schema::table('business_plan_evaluation_questions', function (Blueprint $table) {
            $table->unsignedSmallInteger('format_year')->default(2025)->after('id')
                ->comment('Año desde el que aplica este formato de evaluación');
        });

        Schema::table('business_plan_evaluation_questions', function (Blueprint $table) {
            $table->dropUnique('unique_question_per_role');
            $table->unique(['format_year', 'question_number', 'target_role'], 'unique_question_per_role_format');
            $table->index('format_year');
        });

        $current = DB::table('business_plan_evaluation_questions')
            ->where('format_year', 2025)
            ->where('target_role', 'evaluator')
            ->orderBy('order')
            ->get();

        $now = now();

        foreach ($current as $question) {
            DB::table('business_plan_evaluation_questions')->insert([
                'format_year'     => self::FORMAT_2026,
                'question_number' => $question->question_number,
                'question_text'   => $question->question_text,
                'description'     => $question->question_number === 11
                    ? 'Herramienta utilizada por los jurados para evaluar de manera integral las oportunidades de negocio. Esta evaluación se centra en entender la viabilidad y el potencial de crecimiento futuro de una empresa, más allá de las características inmediatas o específicas que se revisan bajo otros criterios. Esencialmente, permite que el jurado obtenga una comprensión holística y a largo plazo de la empresa, considerando varios factores clave.'
                    : $question->description,
                'target_role'     => 'evaluator',
                'weight'          => self::WEIGHTS_2026[$question->question_number] ?? $question->weight,
                'order'           => $question->order,
                'is_active'       => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('business_plan_evaluation_questions')->where('format_year', '!=', 2025)->delete();

        Schema::table('business_plan_evaluation_questions', function (Blueprint $table) {
            $table->dropUnique('unique_question_per_role_format');
            $table->dropIndex(['format_year']);
            $table->unique(['question_number', 'target_role'], 'unique_question_per_role');
        });

        Schema::table('business_plan_evaluation_questions', function (Blueprint $table) {
            $table->dropColumn('format_year');
        });
    }
};
