<?php

namespace App\Support\ClauseExport;

use App\Models\BusinessPlanEvaluation;
use App\Models\Entrepreneur;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos resumidos que no son una relación directa del emprendedor: conteos de
 * visitas, capacitaciones y ferias, e historial en años anteriores.
 *
 * Se calculan con una consulta agrupada por tipo para todo el lote, no una por
 * emprendedor, y solo si algún campo marcado los necesita.
 */
class ExportContext
{
    /** @var array<int, array{total: int, accepted: int, rescheduled: int, last: ?string}> */
    private array $visits = [];

    /** @var array<int, array{summoned: int, attended: int, hours: float}> */
    private array $trainings = [];

    /** @var array<int, array{count: int, names: string, contacts: bool}> */
    private array $fairs = [];

    /** @var array<int, array{evaluators: int, final: ?float}> */
    private array $evaluations = [];

    /** @var array<string, array{registered: bool, characterized: bool, diagnosed: bool, years: string}> */
    private array $previous = [];

    public function __construct(public readonly int $year)
    {
    }

    /**
     * @param  Collection<int, Entrepreneur>  $entrepreneurs
     * @param  array<string>  $keys  campos marcados
     */
    public function load(Collection $entrepreneurs, array $keys): void
    {
        $ids = $entrepreneurs->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        $needs = fn (string $prefix): bool => collect($keys)->contains(fn ($key) => str_starts_with($key, $prefix));

        if ($needs('visits_')) {
            $this->loadVisits($ids);
        }

        if ($needs('trainings_')) {
            $this->loadTrainings($ids);
        }

        if ($needs('fairs_')) {
            $this->loadFairs($ids);
        }

        if ($needs('evaluation_')) {
            $this->loadEvaluations($ids);
        }

        if ($needs('prev_')) {
            $this->loadPrevious($entrepreneurs);
        }
    }

    public function visits(Entrepreneur $e): array
    {
        return $this->visits[$e->id] ?? ['total' => 0, 'accepted' => 0, 'rescheduled' => 0, 'last' => null];
    }

    public function trainings(Entrepreneur $e): array
    {
        return $this->trainings[$e->id] ?? ['summoned' => 0, 'attended' => 0, 'hours' => 0.0];
    }

    public function fairs(Entrepreneur $e): array
    {
        return $this->fairs[$e->id] ?? ['count' => 0, 'names' => '', 'contacts' => false];
    }

    public function evaluation(Entrepreneur $e): array
    {
        return $this->evaluations[$e->id] ?? ['evaluators' => 0, 'final' => null];
    }

    public function previous(Entrepreneur $e): array
    {
        return $this->previous[(string) $e->document_number] ?? ['registered' => false, 'characterized' => false, 'diagnosed' => false, 'years' => ''];
    }

    /**
     * Al reagendar, la visita original queda marcada como reagendada y se crea
     * una nueva enlazada a ella. Para no contar dos veces la misma visita, el
     * total excluye las originales reagendadas, que se cuentan aparte.
     */
    private function loadVisits(array $ids): void
    {
        $rows = DB::table('visits')
            ->whereNull('deleted_at')
            ->whereIn('entrepreneur_id', $ids)
            ->groupBy('entrepreneur_id')
            ->selectRaw('entrepreneur_id')
            ->selectRaw('SUM(CASE WHEN rescheduled = 0 THEN 1 ELSE 0 END) AS total')
            ->selectRaw("SUM(CASE WHEN visit_result = 'aceptada' THEN 1 ELSE 0 END) AS accepted")
            ->selectRaw('SUM(CASE WHEN rescheduled = 1 THEN 1 ELSE 0 END) AS rescheduled')
            ->selectRaw('MAX(CASE WHEN rescheduled = 0 THEN visit_date END) AS last_date')
            ->get();

        foreach ($rows as $row) {
            $this->visits[$row->entrepreneur_id] = [
                'total'       => (int) $row->total,
                'accepted'    => (int) $row->accepted,
                'rescheduled' => (int) $row->rescheduled,
                'last'        => $row->last_date,
            ];
        }
    }

    private function loadTrainings(array $ids): void
    {
        $rows = DB::table('training_participations as tp')
            ->join('trainings as t', 't.id', '=', 'tp.training_id')
            ->whereNull('tp.deleted_at')
            ->whereNull('t.deleted_at')
            ->whereIn('tp.entrepreneur_id', $ids)
            ->groupBy('tp.entrepreneur_id')
            ->selectRaw('tp.entrepreneur_id')
            ->selectRaw('COUNT(*) AS summoned')
            ->selectRaw('SUM(CASE WHEN tp.attended = 1 THEN 1 ELSE 0 END) AS attended')
            ->selectRaw('SUM(CASE WHEN tp.attended = 1 THEN COALESCE(t.intensity_hours, 0) ELSE 0 END) AS hours')
            ->get();

        foreach ($rows as $row) {
            $this->trainings[$row->entrepreneur_id] = [
                'summoned' => (int) $row->summoned,
                'attended' => (int) $row->attended,
                'hours'    => round((float) $row->hours, 2),
            ];
        }
    }

    private function loadFairs(array $ids): void
    {
        $rows = DB::table('fair_evaluations as fe')
            ->leftJoin('fairs as f', 'f.id', '=', 'fe.fair_id')
            ->whereNull('fe.deleted_at')
            ->whereIn('fe.entrepreneur_id', $ids)
            ->groupBy('fe.entrepreneur_id')
            ->selectRaw('fe.entrepreneur_id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("GROUP_CONCAT(DISTINCT f.name ORDER BY f.name SEPARATOR ', ') AS names")
            ->selectRaw('MAX(fe.generated_contacts) AS contacts')
            ->get();

        foreach ($rows as $row) {
            $this->fairs[$row->entrepreneur_id] = [
                'count'    => (int) $row->total,
                'names'    => (string) $row->names,
                'contacts' => (bool) $row->contacts,
            ];
        }
    }

    /**
     * Evaluación del plan de negocio: cuántos evaluadores lo calificaron y la
     * nota final, con la misma regla del Consolidado (desde 2026 solo
     * evaluadores; antes, 90 % evaluadores + 10 % gestor).
     */
    private function loadEvaluations(array $ids): void
    {
        $rows = DB::table('business_plans as bp')
            ->whereNull('bp.deleted_at')
            ->whereIn('bp.entrepreneur_id', $ids)
            ->selectRaw('bp.entrepreneur_id')
            ->selectRaw("(SELECT COUNT(DISTINCT ev.evaluator_id) FROM business_plan_evaluations ev
                WHERE ev.business_plan_id = bp.id AND ev.evaluator_type = 'evaluator' AND ev.deleted_at IS NULL) AS evaluators")
            ->selectRaw(BusinessPlanEvaluation::finalScoreSql('bp') . ' AS final_score')
            ->get();

        foreach ($rows as $row) {
            $this->evaluations[$row->entrepreneur_id] = [
                'evaluators' => (int) $row->evaluators,
                'final'      => $row->final_score === null ? null : (float) $row->final_score,
            ];
        }
    }

    /**
     * Quien continúa de un año a otro se registra de nuevo, con otro registro
     * pero el mismo documento. El historial se busca por documento.
     *
     * @param  Collection<int, Entrepreneur>  $entrepreneurs
     */
    private function loadPrevious(Collection $entrepreneurs): void
    {
        $documents = $entrepreneurs->pluck('document_number')->filter()->unique()->values()->all();

        if ($documents === []) {
            return;
        }

        $earlier = DB::table('entrepreneurs')
            ->whereNull('deleted_at')
            ->whereIn('document_number', $documents)
            ->whereYear('created_at', '<', $this->year)
            ->get(['id', 'document_number', 'created_at']);

        if ($earlier->isEmpty()) {
            return;
        }

        $earlierIds      = $earlier->pluck('id')->all();
        $characterized   = DB::table('characterizations')->whereNull('deleted_at')->whereIn('entrepreneur_id', $earlierIds)->pluck('entrepreneur_id')->flip();
        $diagnosed       = DB::table('business_diagnoses')->whereNull('deleted_at')->whereIn('entrepreneur_id', $earlierIds)->pluck('entrepreneur_id')->flip();

        foreach ($earlier->groupBy('document_number') as $document => $records) {
            $this->previous[(string) $document] = [
                'registered'    => true,
                'characterized' => $records->contains(fn ($r) => isset($characterized[$r->id])),
                'diagnosed'     => $records->contains(fn ($r) => isset($diagnosed[$r->id])),
                'years'         => $records->map(fn ($r) => substr((string) $r->created_at, 0, 4))->unique()->sort()->implode(', '),
            ];
        }
    }
}
