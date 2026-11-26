<?php

namespace App\Support;

use App\Filament\Resources\CharacterizationResource;
use App\Filament\Resources\TrainingResource;
use App\Filament\Resources\VisitResource;
use App\Models\BusinessDiagnosis;
use App\Models\Entrepreneur;
use App\Models\Pqrf;
use App\Support\ClauseExport\FormOptions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Ruta del emprendedor: todo lo que se muestra en su ficha, armado una sola
 * vez por emprendedor. Las etiquetas de las opciones salen de los formularios
 * (FormOptions), así la ficha dice lo mismo que la pantalla donde se registró.
 */
class EntrepreneurPath
{
    /** @var array<int, array<string, mixed>> */
    private static array $cache = [];

    /** Empleos de la caracterización 2025 (en 2026 se guardan directos e indirectos). */
    private const LEGACY_EMPLOYEES = [
        'up_to_2'     => 'Hasta 2 empleados',
        '3_to_4'      => '3 a 4 empleados',
        'more_than_5' => 'Más de 5 empleados',
    ];

    public static function for(Entrepreneur $entrepreneur): array
    {
        return static::$cache[$entrepreneur->getKey()] ??= static::build($entrepreneur);
    }

    private static function build(Entrepreneur $e): array
    {
        $e->loadMissing([
            'business.economicActivity', 'business.entrepreneurshipStage', 'business.productiveLine',
            'city', 'manager', 'project', 'documentType',
            'characterizations'           => fn ($q) => $q->with(['economicActivity', 'manager'])->latest('id'),
            'businessDiagnoses'           => fn ($q) => $q->with('manager')->latest('id'),
            'visits'                      => fn ($q) => $q->with('manager')->orderByDesc('visit_date')->orderByDesc('visit_time'),
            'trainingParticipations'      => fn ($q) => $q->with('training.city'),
            'fairEvaluations'             => fn ($q) => $q->with('fair')->latest('participation_date'),
            'pqrfs'                       => fn ($q) => $q->latest('id'),
            'businessCanvas', 'businessPlan.evaluations',
        ]);

        $entry = static::diagnosis($e->businessDiagnoses->firstWhere('diagnosis_type', 'entry'));
        $exit  = static::diagnosis($e->businessDiagnoses->firstWhere('diagnosis_type', 'exit'));

        $trainings = $e->trainingParticipations
            ->filter(fn ($p) => $p->training)
            ->sortByDesc(fn ($p) => $p->training->training_date)
            ->values();

        $data = [
            'name'           => $e->full_name,
            'initials'       => collect(explode(' ', (string) $e->full_name))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
            'business'       => $e->business?->business_name,
            'city'           => $e->city?->name,
            'document'       => static::documentLabel($e),
            'phone'          => $e->phone,
            'email'          => $e->email,
            'manager'        => $e->manager?->name,
            'registered'     => $e->created_at?->format('d/m/Y'),
            'project'        => $e->project?->name,
            'route'          => static::route($e),
            'entry'          => $entry,
            'exit'           => $exit,
            'details'        => static::businessDetails($e),
            'description'    => $e->business?->description,
            'characterization' => static::characterization($e),
            'visits'         => static::visits($e),
            'trainings'      => static::trainings($trainings),
            'fairs'          => static::fairs($e),
            'pqrfs'          => static::pqrfs($e),
            'documents'      => static::documents($e),
        ];

        $data['steps'] = static::steps($e, $data, $trainings);

        return $data;
    }

    /** 1.50 → "1,5"; 8.00 → "8". */
    private static function decimal(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, ',', '.'), '0'), ',');
    }

    /** Ruta 1, 2 o 3 según el último diagnóstico y los rangos de su año (igual que Entrepreneur::getRoute). */
    private static function route(Entrepreneur $e): ?int
    {
        return static::placement($e)['route'];
    }

    /**
     * Ruta y nivel de madurez según el último diagnóstico (por puntaje y con
     * los rangos de su año). La usan la ficha y el mapa, para que coincidan.
     * Si los diagnósticos ya están cargados, no consulta la base.
     *
     * @return array{route: ?int, level: ?array{level: int, name: string, label: string, color: string}}
     */
    public static function placement(Entrepreneur $e): array
    {
        $diagnosis = $e->businessDiagnoses->sortByDesc('id')->first();

        if (! $diagnosis || $diagnosis->total_score === null) {
            return ['route' => null, 'level' => null];
        }

        $year  = $diagnosis->created_at?->year ?? now()->year;
        $score = (int) $diagnosis->total_score;
        $route = null;

        foreach (MaturityScale::getPhaseRanges($year) as $phase => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                $route = (int) $phase;
                break;
            }
        }

        return ['route' => $route, 'level' => MaturityScale::getLevelForScore($score, $year)];
    }

    private static function diagnosis(?BusinessDiagnosis $d): ?array
    {
        if (! $d) {
            return null;
        }

        $score = $d->total_score !== null ? (int) $d->total_score : null;

        return [
            'score'        => $score,
            'level'        => $score !== null ? MaturityScale::getLevelForScore($score, $d->created_at?->year ?? now()->year) : null,
            'date'         => ($d->diagnosis_date ?? $d->created_at)?->format('d/m/Y'),
            'manager'      => $d->manager?->name,
            'news'         => $d->has_news
                ? trim((BusinessDiagnosis::newsTypeOptions()[$d->news_type] ?? $d->news_type) . ($d->news_date ? ' · ' . $d->news_date->format('d/m/Y') : ''))
                : null,
            'observations' => $d->general_observations,
        ];
    }

    /** @return array<int, array{label: string, value: ?string}> */
    private static function businessDetails(Entrepreneur $e): array
    {
        return [
            ['label' => 'Documento', 'value' => static::documentLabel($e)],
            ['label' => 'Teléfono', 'value' => $e->phone],
            ['label' => 'Correo electrónico', 'value' => $e->email],
            ['label' => 'Municipio', 'value' => $e->city?->name],
            ['label' => 'Actividad económica', 'value' => $e->business?->economicActivity?->name],
            ['label' => 'Línea productiva', 'value' => $e->business?->productiveLine?->name],
            ['label' => 'Etapa del emprendimiento', 'value' => $e->business?->entrepreneurshipStage?->name],
            ['label' => 'Gestor', 'value' => $e->manager?->name],
            ['label' => 'Registro en el proyecto', 'value' => trim(($e->created_at?->format('d/m/Y') ?? '') . ($e->project ? ' · ' . $e->project->name : '')) ?: null],
        ];
    }

    private static function documentLabel(Entrepreneur $e): ?string
    {
        return $e->document_number ? trim(($e->documentType?->name ?? '') . ' ' . $e->document_number) : null;
    }

    private static function characterization(Entrepreneur $e): ?array
    {
        $c = $e->characterizations->first();

        if (! $c) {
            return null;
        }

        $options = fn (string $field) => FormOptions::for(CharacterizationResource::class, $field);
        $label   = fn (string $field) => $c->{$field} !== null ? ($options($field)[$c->{$field}] ?? $c->{$field}) : null;
        $list    = fn (string $field) => collect(is_array($c->{$field}) ? $c->{$field} : (json_decode((string) $c->{$field}, true) ?: []))
            ->map(fn ($value) => $options($field)[$value] ?? $value)
            ->values()
            ->all();

        $employees = $c->direct_jobs !== null || $c->indirect_jobs !== null
            ? ((int) $c->direct_jobs) . ' directos · ' . ((int) $c->indirect_jobs) . ' indirectos'
            : (self::LEGACY_EMPLOYEES[$c->employees_generated] ?? null);

        return [
            'date'    => ($c->characterization_date ? Carbon::parse($c->characterization_date) : $c->created_at)?->format('d/m/Y'),
            'manager' => $c->manager?->name,
            'details' => [
                ['label' => 'Actividad económica', 'value' => $c->economicActivity?->name ?? $e->business?->economicActivity?->name],
                ['label' => 'Tipo de negocio', 'value' => $label('business_type')],
                ['label' => 'Antigüedad del negocio', 'value' => $label('business_age')],
                ['label' => 'Ventas mensuales promedio', 'value' => $label('average_monthly_sales')],
                ['label' => 'Empleos generados', 'value' => $employees],
                ['label' => 'Registro comercial', 'value' => $c->has_commercial_registration ? 'Sí' : 'No'],
                ['label' => 'Registros contables', 'value' => $c->has_accounting_records ? 'Sí' : 'No'],
            ],
            'lists' => [
                ['label' => 'Estrategias de promoción', 'items' => $list('promotion_strategies')],
                ['label' => 'Clientes', 'items' => $list('clients')],
            ],
        ];
    }

    private static function visits(Entrepreneur $e): array
    {
        $types = FormOptions::for(VisitResource::class, 'visit_type');

        return $e->visits->map(fn ($v) => [
            'date'         => $v->visit_date?->format('d/m/Y'),
            'time'         => $v->visit_time ? Carbon::parse($v->visit_time)->format('h:i a') : null,
            'type'         => $types[$v->visit_type] ?? $v->visit_type,
            'manager'      => $v->manager?->name,
            'confirmed'    => $v->confirmed_at !== null,
            'strengthened' => (bool) $v->strengthened,
            'rescheduled'  => (bool) $v->rescheduled,
        ])->all();
    }

    private static function trainings(Collection $participations): array
    {
        $modalities = FormOptions::for(TrainingResource::class, 'modality');

        return $participations->map(fn ($p) => [
            'name'     => $p->training->name,
            'date'     => $p->training->training_date ? Carbon::parse($p->training->training_date)->format('d/m/Y') : null,
            'modality' => $modalities[$p->training->modality] ?? $p->training->modality,
            'city'     => $p->training->city?->name,
            'hours'    => $p->training->intensity_hours !== null ? static::decimal((float) $p->training->intensity_hours) : null,
            'organizer' => $p->training->organizer_name,
        ])->all();
    }

    private static function fairs(Entrepreneur $e): array
    {
        return $e->fairEvaluations->map(fn ($f) => [
            'name'     => $f->fair?->name,
            'date'     => $f->participation_date ? Carbon::parse($f->participation_date)->format('d/m/Y') : null,
            'sales'    => $f->total_sales !== null ? '$' . number_format((float) $f->total_sales, 0, ',', '.') : null,
            'contacts' => $f->generated_contacts,
        ])->all();
    }

    private static function pqrfs(Entrepreneur $e): array
    {
        return $e->pqrfs->map(fn ($p) => [
            'type'        => Pqrf::typeOptions()[$p->type] ?? $p->type,
            'status'      => Pqrf::statusOptions()[$p->status] ?? $p->status,
            'date'        => $p->incident_date ? Carbon::parse($p->incident_date)->format('d/m/Y') : $p->created_at?->format('d/m/Y'),
            'description' => $p->description,
        ])->all();
    }

    /**
     * Archivos y enlaces agrupados por etapa.
     *
     * @return array<string, array<int, array{label: string, url: string, icon: string}>>
     */
    private static function documents(Entrepreneur $e): array
    {
        $files = function ($value, string $label, string $icon): array {
            $paths = is_array($value) ? $value : (json_decode((string) $value, true) ?? (filled($value) ? [$value] : []));
            $paths = array_values(array_filter((array) $paths, 'filled'));

            return collect($paths)->map(fn ($path, $i) => [
                'label' => $label . (count($paths) > 1 ? ' ' . ($i + 1) : ''),
                'url'   => Storage::disk('public')->url($path),
                'icon'  => $icon,
            ])->all();
        };
        $link = fn (?string $url, string $label) => filled($url) ? [['label' => $label, 'url' => $url, 'icon' => 'heroicon-o-play-circle']] : [];

        $c    = $e->characterizations->first();
        $plan = $e->businessPlan;
        $canvas = $e->businessCanvas;

        return array_filter([
            'Caracterización' => $c ? [
                ...$files($c->commerce_evidence_path, 'Evidencia del comercio', 'heroicon-o-document'),
                ...$files($c->population_evidence_path, 'Evidencia de población', 'heroicon-o-document'),
                ...$files($c->photo_evidence_path, 'Foto de georreferenciación', 'heroicon-o-photo'),
            ] : [],
            'Canvas' => $canvas ? [
                ...$files($canvas->canvas_file_path, 'Documento Canvas', 'heroicon-o-document'),
                ...$link($canvas->fire_pitch_video_url, 'Video Fire Pitch'),
            ] : [],
            'Plan de negocio' => $plan ? [
                ...$files($plan->acquisition_matrix_path, 'Matriz de adquisición', 'heroicon-o-table-cells'),
                ...$files($plan->business_model_path, 'Modelo de negocio', 'heroicon-o-document'),
                ...$files($plan->logo_path, 'Logo', 'heroicon-o-photo'),
                ...$link($plan->fire_pitch_video_url, 'Video Fire Pitch'),
                ...$link($plan->production_cycle_video_url, 'Video ciclo productivo'),
            ] : [],
            'Ferias' => $e->fairEvaluations->flatMap(fn ($f) => $files($f->participation_photo_path, 'Foto · ' . ($f->fair?->name ?? 'Feria'), 'heroicon-o-photo'))->all(),
            'PQRF' => $e->pqrfs->flatMap(fn ($p) => [
                ...$files($p->evidence_files, 'Evidencia · ' . (Pqrf::typeOptions()[$p->type] ?? $p->type), 'heroicon-o-document'),
                ...$files($p->response_files, 'Respuesta · ' . (Pqrf::typeOptions()[$p->type] ?? $p->type), 'heroicon-o-document-check'),
            ])->all(),
        ]);
    }

    /**
     * Etapas del recorrido, en el orden del programa.
     *
     * @return array<int, array{title: string, done: bool, detail: string, icon: string, optional?: bool}>
     */
    private static function steps(Entrepreneur $e, array $data, Collection $trainings): array
    {
        $plural = fn (int $n, string $one, string $many) => $n . ' ' . ($n === 1 ? $one : $many);
        $hours  = $trainings->sum(fn ($p) => (float) $p->training->intensity_hours);
        $plan   = $e->businessPlan;
        $canvas = $e->businessCanvas;
        $entry  = $data['entry'];
        $exit   = $data['exit'];

        // Ruta 1 trabaja con Canvas; rutas 2 y 3, con plan de negocio.
        $planStep = $canvas && ! $plan
            ? ['title' => 'Canvas', 'done' => true, 'icon' => 'heroicon-o-squares-2x2',
               'detail' => $canvas->is_prioritized ? 'Priorizado' : ($canvas->is_potential ? 'Con potencial' : 'Registrado')]
            : ['title' => 'Plan de negocio', 'done' => (bool) $plan, 'icon' => 'heroicon-o-document-chart-bar',
               'detail' => ! $plan ? 'Pendiente'
                   : ($plan->is_prioritized ? 'Priorizado' : 'Registrado')
                       . ($plan->evaluations->isNotEmpty() ? ' · calificación ' . static::decimal((float) $plan->final_score) : '')];

        return [
            ['title' => 'Caracterización', 'done' => (bool) $data['characterization'], 'icon' => 'heroicon-o-clipboard-document-check',
             'detail' => $data['characterization']['date'] ?? 'Pendiente'],
            ['title' => 'Diagnóstico de entrada', 'done' => (bool) $entry, 'icon' => 'heroicon-o-chart-bar',
             'detail' => $entry ? ($entry['score'] !== null ? $entry['score'] . ' puntos' : 'Sin puntaje') : 'Pendiente'],
            ['title' => 'Visitas', 'done' => $data['visits'] !== [], 'icon' => 'heroicon-o-map-pin',
             'detail' => $data['visits'] !== [] ? $plural(count($data['visits']), 'visita', 'visitas') : 'Ninguna'],
            ['title' => 'Capacitaciones', 'done' => $trainings->isNotEmpty(), 'icon' => 'heroicon-o-academic-cap',
             'detail' => $trainings->isNotEmpty() ? $plural($trainings->count(), 'capacitación', 'capacitaciones') . ($hours > 0 ? ' · ' . static::decimal($hours) . ' h' : '') : 'Ninguna'],
            $planStep,
            // Opcional: no todos participan en ferias, así que sin ferias no se marca como pendiente.
            ['title' => 'Ferias', 'done' => $data['fairs'] !== [], 'icon' => 'heroicon-o-building-storefront', 'optional' => true,
             'detail' => $data['fairs'] !== [] ? $plural(count($data['fairs']), 'feria', 'ferias') : 'Ninguna'],
            ['title' => 'Diagnóstico de salida', 'done' => (bool) $exit, 'icon' => 'heroicon-o-flag',
             'detail' => $exit && $exit['score'] !== null
                 ? $exit['score'] . ' puntos' . ($entry && $entry['score'] !== null ? ' (' . sprintf('%+d', $exit['score'] - $entry['score']) . ')' : '')
                 : 'Pendiente'],
        ];
    }
}
