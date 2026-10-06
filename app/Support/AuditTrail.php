<?php

namespace App\Support;

use App\Models\BusinessPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Registros de actividad para operaciones que escriben en bloque.
 *
 * Los borrados y restauraciones masivos (`Model::where(...)->delete()`) no
 * disparan los eventos de cada modelo, así que el historial no los vería. En
 * lugar de un registro por fila se deja una sola acción que resume qué pasó.
 *
 * Un fallo al registrar nunca debe impedir la operación.
 */
class AuditTrail
{
    public const EVALUATOR_LABELS = [
        'evaluator' => 'evaluador',
        'manager'   => 'gestor',
    ];

    /**
     * Un evaluador o gestor eliminó su evaluación completa de un plan de negocio.
     * Se guarda copia de las calificaciones, para saber qué había.
     *
     * @param  Collection<int, \App\Models\BusinessPlanEvaluation>  $ratings
     */
    public static function evaluationDeleted(BusinessPlan $plan, string $evaluatorType, Collection $ratings): void
    {
        if ($ratings->isEmpty()) {
            return;
        }

        try {
            $role = self::EVALUATOR_LABELS[$evaluatorType] ?? $evaluatorType;

            activity('evaluation')
                ->performedOn($plan)
                ->event('evaluation_deleted')
                ->withProperties([
                    'evaluator_type' => $role,
                    'count'          => $ratings->count(),
                    'ratings'        => $ratings->map(fn ($rating) => [
                        'question' => $rating->question?->question_text ?? 'Pregunta #'.$rating->question_id,
                        'score'    => (string) $rating->score,
                    ])->values()->all(),
                ])
                ->log(sprintf(
                    'Eliminó su evaluación como %s (%d %s)',
                    $role,
                    $ratings->count(),
                    $ratings->count() === 1 ? 'calificación' : 'calificaciones',
                ));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Registros deshabilitados o restaurados junto con otro.
     *
     * @param  array<string, int>  $counts  Etiqueta en plural => cantidad afectada.
     */
    public static function cascade(Model $subject, string $event, array $counts): void
    {
        $counts = array_filter($counts);

        if ($counts === []) {
            return;
        }

        try {
            $verb = $event === 'cascade_restored' ? 'Restauró' : 'Deshabilitó';

            $parts = [];
            foreach ($counts as $label => $count) {
                $parts[] = $count.' '.$label;
            }

            activity('model')
                ->performedOn($subject)
                ->event($event)
                ->withProperties(['affected' => $counts])
                ->log($verb.' en cascada: '.implode(', ', $parts));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
