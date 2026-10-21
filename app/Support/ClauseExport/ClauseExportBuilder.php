<?php

namespace App\Support\ClauseExport;

use App\Models\Entrepreneur;
use App\Scopes\YearColumnScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Arma la exportación por cláusulas a partir de una configuración: la misma
 * que se muestra en pantalla y la que se guarda en las plantillas.
 *
 * El año se aplica de forma explícita y se ignora el filtro global por año:
 * la exportación cubre el año escogido en la propia pantalla, no el del
 * selector general del panel.
 */
class ClauseExportBuilder
{
    /** Documentos del plan que se pueden usar como condición. */
    public const PLAN_DOCUMENTS = [
        'business_plan_path'         => 'PDF del plan de negocio',
        'acquisition_matrix_path'    => 'Matriz de adquisiciones',
        'business_model_path'        => 'Modelo de negocio',
        'fire_pitch_video_url'       => 'Video Fire Pitch',
        'production_cycle_video_url' => 'Video ciclo productivo',
        'logo_path'                  => 'Logo',
    ];

    /**
     * @param  array<int>  $excludedCities
     * @param  array<string>  $columns  claves del catálogo, en orden
     * @param  array<string, mixed>  $conditions
     */
    public function __construct(
        public readonly string $clause,
        public readonly array $excludedCities,
        public readonly int $year,
        public readonly array $columns,
        public readonly array $conditions = [],
    ) {
    }

    /** @param  array<string, mixed>  $config */
    public static function fromConfig(array $config): self
    {
        return new self(
            clause: (string) ($config['clause'] ?? Clauses::LA_LOMA),
            excludedCities: array_map('intval', $config['excluded_cities'] ?? []),
            year: (int) ($config['year'] ?? now()->year),
            columns: array_values(array_filter($config['columns'] ?? [], fn ($key) => FieldCatalog::get($key) !== null)),
            conditions: (array) ($config['conditions'] ?? []),
        );
    }

    /** @return array<int> */
    public function cityIds(): array
    {
        return array_values(array_diff(Clauses::cityIds($this->clause), $this->excludedCities));
    }

    public function query(): Builder
    {
        $query = Entrepreneur::query()
            ->withoutGlobalScope(YearColumnScope::class)
            ->whereYear('entrepreneurs.created_at', $this->year)
            ->whereIn('entrepreneurs.city_id', $this->cityIds() ?: [0]);

        $this->applyConditions($query);

        return $query->orderBy('entrepreneurs.city_id')->orderBy('entrepreneurs.full_name');
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Cláusula', ...array_map(fn ($key) => FieldCatalog::get($key)['label'], $this->columns)];
    }

    /**
     * Una fila por emprendedor: la cláusula y luego las columnas marcadas.
     *
     * @return array<int, array<int, mixed>>
     */
    public function rows(?int $limit = null): array
    {
        $entrepreneurs = $this->query()
            ->with($this->eagerLoads())
            ->when($limit, fn (Builder $query) => $query->limit($limit))
            ->get();

        $context = new ExportContext($this->year);
        $context->load($entrepreneurs, $this->columns);

        return $entrepreneurs->map(function (Entrepreneur $entrepreneur) use ($context): array {
            $row = [Clauses::nameForCity($entrepreneur->city_id)];

            foreach ($this->columns as $key) {
                $value = (FieldCatalog::get($key)['value'])($entrepreneur, $context);
                $row[] = $value ?? '';
            }

            return $row;
        })->all();
    }

    /**
     * Solo las relaciones de los campos marcados, y sin el filtro global por
     * año: los registros del emprendedor pertenecen al año del propio registro.
     *
     * @return array<string, \Closure>
     */
    private function eagerLoads(): array
    {
        $paths = [];

        foreach (FieldCatalog::relationsFor($this->columns) as $relation) {
            // Una relación anidada necesita también a su padre sin el filtro de año.
            $segments = explode('.', $relation);

            foreach (array_keys($segments) as $i) {
                $paths[implode('.', array_slice($segments, 0, $i + 1))] = true;
            }
        }

        $withoutYear = fn ($query) => $query->withoutGlobalScope(YearColumnScope::class);

        return array_fill_keys(array_keys($paths), $withoutYear);
    }

    /**
     * Condiciones opcionales para afinar el resultado.
     */
    private function applyConditions(Builder $query): void
    {
        $c = $this->conditions;
        $withoutYear = fn ($q) => $q->withoutGlobalScope(YearColumnScope::class);

        if (filled($c['routes'] ?? null)) {
            $query->whereIn('entrepreneurs.id', Entrepreneur::getIdsByRoute((array) $c['routes']) ?: [0]);
        }

        foreach (['genders' => 'gender_id', 'managers' => 'manager_id', 'populations' => 'population_id'] as $key => $column) {
            if (filled($c[$key] ?? null)) {
                $query->whereIn("entrepreneurs.{$column}", (array) $c[$key]);
            }
        }

        // Tiene / no tiene un registro de cada módulo.
        $presence = [
            'has_characterization' => fn (Builder $q, bool $has) => $has
                ? $q->whereHas('characterizations', $withoutYear)
                : $q->whereDoesntHave('characterizations', $withoutYear),
            'has_entry_diagnosis'  => fn (Builder $q, bool $has) => $this->diagnosisPresence($q, 'entry', $has),
            'has_exit_diagnosis'   => fn (Builder $q, bool $has) => $this->diagnosisPresence($q, 'exit', $has),
            'has_plan'             => fn (Builder $q, bool $has) => $has
                ? $q->whereHas('businessPlan', $withoutYear)
                : $q->whereDoesntHave('businessPlan', $withoutYear),
            'has_canvas'           => fn (Builder $q, bool $has) => $has
                ? $q->whereHas('businessCanvas', $withoutYear)
                : $q->whereDoesntHave('businessCanvas', $withoutYear),
        ];

        foreach ($presence as $key => $apply) {
            if (($c[$key] ?? null) !== null && ($c[$key] ?? '') !== '') {
                $apply($query, (bool) (int) $c[$key]);
            }
        }

        // Documento del plan adjunto o no. "No adjuntado" incluye a quien no
        // tiene plan, que es como se lee en lenguaje natural.
        $document = $c['plan_document'] ?? null;
        $attached = $c['plan_document_attached'] ?? null;

        if (isset(self::PLAN_DOCUMENTS[$document]) && $attached !== null && $attached !== '') {
            $filled = fn ($q) => $withoutYear($q)->whereNotNull($document)->where($document, '!=', '');

            (bool) (int) $attached
                ? $query->whereHas('businessPlan', $filled)
                : $query->whereDoesntHave('businessPlan', $filled);
        }
    }

    private function diagnosisPresence(Builder $query, string $type, bool $has): void
    {
        $constraint = fn ($q) => $q->withoutGlobalScope(YearColumnScope::class)->where('diagnosis_type', $type);

        $has
            ? $query->whereHas('businessDiagnoses', $constraint)
            : $query->whereDoesntHave('businessDiagnoses', $constraint);
    }

    /**
     * Resumen legible de la configuración, para el historial de acciones.
     *
     * @return array<string, mixed>
     */
    public function describe(): array
    {
        return [
            'clause'     => Clauses::options()[$this->clause] ?? $this->clause,
            'year'       => $this->year,
            'cities'     => array_values(Clauses::cities($this->clause) === [] ? [] : array_intersect_key(Clauses::cities($this->clause), array_flip($this->cityIds()))),
            'columns'    => array_slice($this->headings(), 1),
            'conditions' => array_filter($this->conditions, fn ($value) => $value !== null && $value !== '' && $value !== []),
        ];
    }
}
