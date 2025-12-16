<?php

namespace Database\Seeders;

use App\Models\BusinessPlanEvaluationQuestion;
use Illuminate\Database\Seeder;

class BusinessPlanEvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // PREGUNTAS PARA EVALUADORES (1-10)
            [
                'question_number' => 1,
                'question_text' => 'Claridad y coherencia de la presentación',
                'description' => 'Se evidencia la capacidad del emprendedor para comunicar su propuesta de valor, modelo de negocio y plan de ejecución de manera clara y coherente.',
                'target_role' => 'evaluator',
                'weight' => 0.05,
                'order' => 1,
            ],
            [
                'question_number' => 2,
                'question_text' => 'Preparación y conocimiento del equipo',
                'description' => 'Se evidencia el nivel de preparación y conocimiento del emprendedor sobre el negocio y la industria.',
                'target_role' => 'evaluator',
                'weight' => 0.05,
                'order' => 2,
            ],
            [
                'question_number' => 3,
                'question_text' => 'Situación y necesidades de capital',
                'description' => 'Describe correctamente la situación financiera actual de la empresa, incluyendo su historial de inversiones previas, gastos operativos y necesidades de capital a corto y mediano plazo.',
                'target_role' => 'evaluator',
                'weight' => 0.05,
                'order' => 3,
            ],
            [
                'question_number' => 4,
                'question_text' => 'Tracción / Métricas Clave',
                'description' => 'Identifica métricas clave de rendimiento, como ingresos, tasas de crecimiento, adquisición y retención de clientes, entre otros indicadores relevantes.',
                'target_role' => 'evaluator',
                'weight' => 0.05,
                'order' => 4,
            ],
            [
                'question_number' => 5,
                'question_text' => 'Producto / Servicio',
                'description' => 'El emprendedor tiene claridad en calidad, diferenciación y escalabilidad del producto o servicio ofrecido por la empresa.',
                'target_role' => 'evaluator',
                'weight' => 0.05,
                'order' => 5,
            ],
            [
                'question_number' => 6,
                'question_text' => 'Canales / Distribución y ventas',
                'description' => 'Se evidencia la eficacia de los canales de distribución y estrategias de ventas de la empresa.',
                'target_role' => 'evaluator',
                'weight' => 0.05,
                'order' => 6,
            ],
            [
                'question_number' => 7,
                'question_text' => 'Mercado y oportunidad',
                'description' => 'Analiza correctamente el tamaño y potencial de crecimiento del mercado objetivo, así como la capacidad del emprendedor para capturar una parte significativa del mismo.',
                'target_role' => 'evaluator',
                'weight' => 0.10,
                'order' => 7,
            ],
            [
                'question_number' => 8,
                'question_text' => 'Comprensión del mercado y la competencia',
                'description' => 'Se evidencia el nivel de comprensión del emprendedor sobre el mercado en el que opera y sus competidores.',
                'target_role' => 'evaluator',
                'weight' => 0.10,
                'order' => 8,
            ],
            [
                'question_number' => 9,
                'question_text' => 'Plan / ejecución y escalabilidad',
                'description' => 'Se demuestra la solidez del plan de ejecución y la capacidad del emprendedor para escalar su modelo de negocio de manera eficiente y rentable.',
                'target_role' => 'evaluator',
                'weight' => 0.10,
                'order' => 9,
            ],
            [
                'question_number' => 10,
                'question_text' => 'Modelo de negocio',
                'description' => 'Se demuestra la solidez y viabilidad del modelo de negocio, incluyendo su propuesta de valor, fuentes de ingresos, costos y ventajas competitivas.',
                'target_role' => 'evaluator',
                'weight' => 0.10,
                'order' => 10,
            ],
            [
                'question_number' => 11,
                'question_text' => 'Criterio global (Evaluación integral)',
                'description' => 'Herramienta utilizada por los jurados para evaluar de manera integral las oportunidades de negocio. Considera: Escalabilidad, Tendencias y disrupciones del mercado, Adaptabilidad del equipo emprendedor, Barreras de entrada y competencia, Expansión de mercados y productos, Retorno de la inversión.',
                'target_role' => 'evaluator',
                'weight' => 0.20,
                'order' => 11,
            ],

            // PREGUNTA PARA GESTORES
            [
                'question_number' => 11,
                'question_text' => 'Criterio técnico gestor',
                'description' => 'I. El modelo negocio es escalable, sostenible, posee ventaja competitiva. II. La propuesta de valor ha sido testeada y ajustada de acuerdo con las necesidades del mercado. III. Emplea prácticas amigables con el medio ambiente. IV. El emprendedor tiene diseñada la ruta hacia la formalización empresarial.',
                'target_role' => 'manager',
                'weight' => 0.10,
                'order' => 12,
            ],
        ];

        foreach ($questions as $question) {
            BusinessPlanEvaluationQuestion::create($question);
        }
    }
}
