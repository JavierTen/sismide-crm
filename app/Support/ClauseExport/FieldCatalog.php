<?php

namespace App\Support\ClauseExport;

use App\Filament\Resources\BusinessDiagnosisResource;
use App\Filament\Resources\CharacterizationResource;
use App\Models\BusinessDiagnosis;
use App\Models\Entrepreneur;
use App\Support\MaturityScale;
use Closure;
use Illuminate\Support\Carbon;

/**
 * Catálogo de datos que se pueden escoger en la exportación por cláusulas.
 *
 * Es la única fuente: de aquí salen los grupos y el buscador de la pantalla,
 * la vista previa y las columnas del Excel. Cada campo declara qué relaciones
 * necesita, así la consulta solo carga lo que se marcó.
 *
 * Reglas del documento:
 *  - Si el emprendedor aún no tiene el registro (sin plan, sin diagnóstico…),
 *    sus campos dicen "Sin registro".
 *  - Los documentos adjuntos salen como Sí / No.
 *  - Visitas, capacitaciones y ferias salen resumidas.
 */
class FieldCatalog
{
    public const NO_RECORD = 'Sin registro';

    /** @var array<string, array{key: string, label: string, group: string, with: array<string>, value: Closure}>|null */
    private static ?array $fields = null;

    /**
     * Grupos en el orden en que se muestran.
     *
     * @return array<string, array<int, array{key: string, label: string}>>
     */
    public static function groups(): array
    {
        $groups = [];

        foreach (static::all() as $field) {
            $groups[$field['group']][] = ['key' => $field['key'], 'label' => $field['label']];
        }

        return $groups;
    }

    /**
     * @return array<string, array{key: string, label: string, group: string, with: array<string>, value: Closure}>
     */
    public static function all(): array
    {
        if (static::$fields !== null) {
            return static::$fields;
        }

        $fields = [];

        foreach (static::definitions() as $group => $definitions) {
            foreach ($definitions as $key => [$label, $with, $value]) {
                $fields[$key] = ['key' => $key, 'label' => $label, 'group' => $group, 'with' => $with, 'value' => $value];
            }
        }

        return static::$fields = $fields;
    }

    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /** @param  array<string>  $keys */
    public static function relationsFor(array $keys): array
    {
        return collect($keys)
            ->map(fn (string $key) => static::get($key)['with'] ?? [])
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    // ── Definiciones ───────────────────────────────────────────────────────

    /**
     * grupo => [clave => [etiqueta, relaciones, valor]]
     *
     * @return array<string, array<string, array{0: string, 1: array<string>, 2: Closure}>>
     */
    private static function definitions(): array
    {
        $char = fn (Entrepreneur $e) => $e->characterizations->sortByDesc('id')->first();
        $plan = fn (Entrepreneur $e) => $e->businessPlan;
        $canvas = fn (Entrepreneur $e) => $e->businessCanvas;
        $biz = fn (Entrepreneur $e) => $e->business;

        $charOptions = fn (string $field): array => FormOptions::for(CharacterizationResource::class, $field);

        // Campo de un registro que puede no existir.
        $from = fn (Closure $record, Closure $read) => function (Entrepreneur $e, ExportContext $ctx) use ($record, $read) {
            $model = $record($e);

            return $model ? $read($model, $ctx) : self::NO_RECORD;
        };

        return [
            'Emprendedor' => [
                'document_type'   => ['Tipo de documento', ['documentType'], fn ($e) => $e->documentType?->name],
                'document_number' => ['Documento', [], fn ($e) => $e->document_number],
                'full_name'       => ['Nombre', [], fn ($e) => $e->full_name],
                'gender'          => ['Género', ['gender'], fn ($e) => $e->gender?->name],
                'age'             => ['Edad', [], fn ($e) => $e->birth_date ? Carbon::parse($e->birth_date)->age : null],
                'birth_date'      => ['Fecha de nacimiento', [], fn ($e) => static::date($e->birth_date)],
                'marital_status'  => ['Estado civil', ['maritalStatus'], fn ($e) => $e->maritalStatus?->name],
                'population'      => ['Población vulnerable', ['population'], fn ($e) => $e->population?->name],
                'education_level' => ['Nivel educativo', ['educationLevel'], fn ($e) => $e->educationLevel?->name],
                'phone'           => ['Teléfono', [], fn ($e) => $e->phone],
                'phone_2'         => ['Teléfono 2', [], fn ($e) => $e->phone_2],
                'email'           => ['Correo', [], fn ($e) => $e->email],
                'home_address'    => ['Dirección de residencia', [], fn ($e) => $e->address],
                'admission_date'  => ['Fecha de ingreso', [], fn ($e) => static::date($e->admission_date)],
            ],

            'Emprendimiento' => [
                'business_name'       => ['Nombre del emprendimiento', ['business'], $from($biz, fn ($b) => $b->business_name)],
                'chamber_of_commerce' => ['Cámara de Comercio', ['business'], $from($biz, fn ($b) => static::yesNo($b->has_chamber_of_commerce))],
                'stage'               => ['Etapa', ['business.entrepreneurshipStage'], $from($biz, fn ($b) => $b->entrepreneurshipStage?->name)],
                'economic_activity'   => ['Actividad económica', ['business.economicActivity'], $from($biz, fn ($b) => $b->economicActivity?->name)],
                'productive_line'     => ['Línea productiva', ['business.productiveLine'], $from($biz, fn ($b) => $b->productiveLine?->name)],
                'ciiu'                => ['CIIU', ['business.ciiuCode'], $from($biz, fn ($b) => $b->ciiuCode ? trim($b->ciiuCode->code.' - '.$b->ciiuCode->descripcion, ' -') : null)],
                'project'             => ['Proyecto', ['project'], fn ($e) => $e->project?->name],
                'cohort'              => ['Cohorte', ['business'], $from($biz, fn ($b) => static::cohort($b->cohort))],
                'route'               => ['Ruta', ['businessDiagnoses'], fn ($e) => static::routeLabel(static::latestDiagnosis($e))],
                'maturity'            => ['Nivel de madurez', ['businessDiagnoses'], fn ($e) => static::maturityLabel(static::latestDiagnosis($e))],
                'manager'             => ['Gestor', ['manager'], fn ($e) => $e->manager?->name],
                'city'                => ['Municipio', ['city'], fn ($e) => $e->city?->name],
                'business_address'    => ['Dirección del emprendimiento', ['business'], $from($biz, fn ($b) => $b->address)],
                'business_created'    => ['Fecha de creación del negocio', ['business'], $from($biz, fn ($b) => static::date($b->creation_date))],
            ],

            'Caracterización' => [
                'char_date'         => ['Fecha de caracterización', ['characterizations'], $from($char, fn ($c) => static::date($c->characterization_date))],
                'business_type'     => ['Tipo de negocio', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('business_type'), $c->business_type))],
                'business_age'      => ['Antigüedad del negocio', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('business_age'), $c->business_age))],
                'business_state'    => ['Estado actual del negocio', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('business_current_state'), $c->business_current_state))],
                'market_coverage'   => ['Mercado (cobertura)', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('market_coverage'), $c->market_coverage))],
                'clients'           => ['Clientes', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('clients'), $c->clients))],
                'monthly_sales'     => ['Ventas mensuales promedio', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('average_monthly_sales'), $c->average_monthly_sales))],
                'direct_jobs'       => ['Empleos directos', ['characterizations'], $from($char, fn ($c) => $c->direct_jobs)],
                'indirect_jobs'     => ['Empleos indirectos', ['characterizations'], $from($char, fn ($c) => $c->indirect_jobs)],
                'commercial_reg'    => ['Registro mercantil', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->has_commercial_registration))],
                'licenses'          => ['Licencias de funcionamiento', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->has_operation_licenses))],
                'accounting'        => ['Lleva contabilidad', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->has_accounting_records))],
                'bank_account'      => ['Cuenta bancaria del negocio', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->has_business_bank_account))],
                'monthly_costs'     => ['Costos mensuales', ['characterizations'], $from($char, fn ($c) => $c->monthly_costs)],
                'monthly_expenses'  => ['Gastos mensuales', ['characterizations'], $from($char, fn ($c) => $c->monthly_expenses)],
                'monthly_profit'    => ['Utilidad mensual', ['characterizations'], $from($char, fn ($c) => $c->monthly_profit)],
                'active_credits'    => ['Tiene créditos activos', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->has_active_credits))],
                'credit_entity'     => ['Entidad del crédito', ['characterizations'], $from($char, fn ($c) => $c->credit_entity)],
                'credit_amount'     => ['Monto del crédito', ['characterizations'], $from($char, fn ($c) => $c->credit_amount)],
                'drummond'          => ['Familiares en Drummond', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->family_in_drummond))],
                'hires_women'       => ['Contrata mujeres', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->hires_women))],
                'women_employees'   => ['N.º de mujeres empleadas', ['characterizations'], $from($char, fn ($c) => $c->women_employees_count)],
                'tech_level'        => ['Capacidad tecnológica', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('tech_capacity_level'), $c->tech_capacity_level))],
                'digital_tools'     => ['Herramientas digitales', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('digital_tools'), $c->digital_tools))],
                'needs'             => ['Necesidades de fortalecimiento', ['characterizations'], $from($char, fn ($c) => FormOptions::label($charOptions('strengthening_needs'), $c->strengthening_needs))],
                'commerce_evidence' => ['Evidencia comercial adjunta', ['characterizations'], $from($char, fn ($c) => static::hasFile($c->commerce_evidence_path))],
                'population_evid'   => ['Evidencia poblacional adjunta', ['characterizations'], $from($char, fn ($c) => static::hasFile($c->population_evidence_path))],
                'photo_evidence'    => ['Evidencia fotográfica adjunta', ['characterizations'], $from($char, fn ($c) => static::hasFile($c->photo_evidence_path))],
                'habeas_data'       => ['Habeas Data aceptado', ['characterizations'], $from($char, fn ($c) => static::yesNo($c->habeas_data_accepted))],
            ],

            'Diagnóstico de entrada' => static::diagnosisFields('entry', 'entrada'),

            'Diagnóstico de salida' => static::diagnosisFields('exit', 'salida'),

            'Plan de negocio' => [
                'has_plan'           => ['¿Tiene plan de negocio?', ['businessPlan'], fn ($e) => static::yesNo((bool) $e->businessPlan)],
                'plan_profitability' => ['Rentabilidad bruta (%)', ['businessPlan'], $from($plan, fn ($p) => $p->gross_profitability_rate)],
                'plan_irr'           => ['TIR (%)', ['businessPlan'], $from($plan, fn ($p) => $p->internal_return_rate)],
                'plan_break_units'   => ['Punto de equilibrio (unidades)', ['businessPlan'], $from($plan, fn ($p) => $p->break_even_units)],
                'plan_break_cop'     => ['Punto de equilibrio (COP)', ['businessPlan'], $from($plan, fn ($p) => $p->break_even_cop)],
                'plan_investment'    => ['Inversión actual (COP)', ['businessPlan'], $from($plan, fn ($p) => $p->current_investment_value)],
                'plan_sales'         => ['Ventas mensuales (COP)', ['businessPlan'], $from($plan, fn ($p) => $p->monthly_sales_cop)],
                'plan_jobs'          => ['Empleos generados', ['businessPlan'], $from($plan, fn ($p) => $p->jobs_generated)],
                'plan_capitalized'   => ['Capitalizado', ['businessPlan'], $from($plan, fn ($p) => static::yesNo($p->is_capitalized))],
                'plan_prioritized'   => ['Priorizado', ['businessPlan'], $from($plan, fn ($p) => static::yesNo($p->is_prioritized))],
                'plan_pdf'           => ['Adjuntó PDF del plan', ['businessPlan'], $from($plan, fn ($p) => static::hasFile($p->business_plan_path))],
                'plan_matrix'        => ['Adjuntó matriz de adquisiciones', ['businessPlan'], $from($plan, fn ($p) => static::hasFile($p->acquisition_matrix_path))],
                'plan_model'         => ['Adjuntó modelo de negocio', ['businessPlan'], $from($plan, fn ($p) => static::hasFile($p->business_model_path))],
                'plan_pitch'         => ['Video Fire Pitch', ['businessPlan'], $from($plan, fn ($p) => static::hasFile($p->fire_pitch_video_url))],
                'plan_cycle_video'   => ['Video ciclo productivo', ['businessPlan'], $from($plan, fn ($p) => static::hasFile($p->production_cycle_video_url))],
                'plan_logo'          => ['Adjuntó logo', ['businessPlan'], $from($plan, fn ($p) => static::hasFile($p->logo_path))],
            ],

            'Canvas' => [
                'has_canvas'         => ['¿Tiene Modelo Canvas?', ['businessCanvas'], fn ($e) => static::yesNo((bool) $e->businessCanvas)],
                'canvas_file'        => ['Adjuntó archivo del Canvas', ['businessCanvas'], $from($canvas, fn ($c) => static::hasFile($c->canvas_file_path))],
                'canvas_pitch'       => ['Video Fire Pitch (Canvas)', ['businessCanvas'], $from($canvas, fn ($c) => static::hasFile($c->fire_pitch_video_url))],
                'canvas_potential'   => ['Canvas con potencial', ['businessCanvas'], $from($canvas, fn ($c) => static::yesNo($c->is_potential))],
                'canvas_prioritized' => ['Canvas priorizado', ['businessCanvas'], $from($canvas, fn ($c) => static::yesNo($c->is_prioritized))],
            ],

            'Visitas' => [
                'visits_count'       => ['N.º de visitas', [], fn ($e, $ctx) => $ctx->visits($e)['total']],
                'visits_accepted'    => ['Visitas aceptadas', [], fn ($e, $ctx) => $ctx->visits($e)['accepted']],
                'visits_rescheduled' => ['Visitas reagendadas', [], fn ($e, $ctx) => $ctx->visits($e)['rescheduled']],
                'visits_last'        => ['Fecha de la última visita', [], fn ($e, $ctx) => static::date($ctx->visits($e)['last']) ?? self::NO_RECORD],
            ],

            'Capacitaciones' => [
                'trainings_attended' => ['Capacitaciones asistidas', [], fn ($e, $ctx) => $ctx->trainings($e)['attended']],
                'trainings_summoned' => ['Capacitaciones convocadas', [], fn ($e, $ctx) => $ctx->trainings($e)['summoned']],
                'trainings_hours'    => ['Horas de formación', [], fn ($e, $ctx) => $ctx->trainings($e)['hours']],
                'trainings_rate'     => ['% de asistencia', [], fn ($e, $ctx) => $ctx->trainings($e)['summoned'] > 0
                    ? round($ctx->trainings($e)['attended'] * 100 / $ctx->trainings($e)['summoned'], 1).'%'
                    : self::NO_RECORD],
            ],

            'Ferias' => [
                'fairs_count'    => ['N.º de ferias', [], fn ($e, $ctx) => $ctx->fairs($e)['count']],
                'fairs_names'    => ['Nombres de las ferias', [], fn ($e, $ctx) => $ctx->fairs($e)['names'] ?: self::NO_RECORD],
                'fairs_contacts' => ['Generó contactos o alianzas', [], fn ($e, $ctx) => $ctx->fairs($e)['count'] > 0
                    ? static::yesNo($ctx->fairs($e)['contacts'])
                    : self::NO_RECORD],
            ],

            'Años anteriores' => [
                'prev_registered'    => ['Registrado en un año anterior', [], fn ($e, $ctx) => static::yesNo($ctx->previous($e)['registered'])],
                'prev_characterized' => ['Caracterizado en un año anterior', [], fn ($e, $ctx) => static::yesNo($ctx->previous($e)['characterized'])],
                'prev_diagnosed'     => ['Diagnosticado en un año anterior', [], fn ($e, $ctx) => static::yesNo($ctx->previous($e)['diagnosed'])],
                'prev_years'         => ['Años anteriores en que aparece', [], fn ($e, $ctx) => $ctx->previous($e)['years'] ?: '—'],
            ],
        ];
    }

    /**
     * Mismos datos para el diagnóstico de entrada y el de salida.
     *
     * @return array<string, array{0: string, 1: array<string>, 2: Closure}>
     */
    private static function diagnosisFields(string $type, string $suffix): array
    {
        $diagnosis = fn (Entrepreneur $e): ?BusinessDiagnosis => $e->businessDiagnoses
            ->where('diagnosis_type', $type)
            ->sortByDesc('id')
            ->first();

        $read = fn (Closure $value) => function (Entrepreneur $e) use ($diagnosis, $value) {
            $record = $diagnosis($e);

            return $record ? $value($record) : self::NO_RECORD;
        };

        $newsOptions = fn (): array => FormOptions::for(BusinessDiagnosisResource::class, 'news_type');

        return [
            "diag_{$type}_date"     => ["Fecha diagnóstico de {$suffix}", ['businessDiagnoses'], $read(fn ($d) => static::date($d->diagnosis_date))],
            "diag_{$type}_score"    => ["Puntaje diagnóstico de {$suffix}", ['businessDiagnoses'], $read(fn ($d) => $d->total_score)],
            "diag_{$type}_route"    => ["Ruta resultante ({$suffix})", ['businessDiagnoses'], $read(fn ($d) => static::routeLabel($d))],
            "diag_{$type}_maturity" => ["Nivel de madurez ({$suffix})", ['businessDiagnoses'], $read(fn ($d) => static::maturityLabel($d))],
            "diag_{$type}_news"     => ["Novedades ({$suffix})", ['businessDiagnoses'], $read(fn ($d) => $d->has_news
                ? (FormOptions::label($newsOptions(), $d->news_type) ?? 'Sí')
                : 'Ninguna')],
        ];
    }

    // ── Formato ────────────────────────────────────────────────────────────

    public static function yesNo(mixed $value): string
    {
        return $value ? 'Sí' : 'No';
    }

    /** Un adjunto existe si la ruta (o la lista de rutas) no está vacía. */
    public static function hasFile(mixed $value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value   = is_array($decoded) ? $decoded : trim($value);
        }

        return static::yesNo(filled($value));
    }

    public static function date(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private static function cohort(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return is_numeric($value) ? 'Cohorte '.$value : mb_convert_case($value, MB_CASE_TITLE);
    }

    /** Diagnóstico más reciente, de cualquier tipo: el que define la ruta. */
    private static function latestDiagnosis(Entrepreneur $e): ?BusinessDiagnosis
    {
        return $e->businessDiagnoses->sortByDesc('id')->first();
    }

    /** Misma regla que Entrepreneur::getRoute(): fase según el puntaje y el año. */
    public static function routeLabel(?BusinessDiagnosis $diagnosis): string
    {
        if (! $diagnosis || $diagnosis->total_score === null) {
            return self::NO_RECORD;
        }

        $year = $diagnosis->created_at?->year ?? now()->year;

        foreach (MaturityScale::getPhaseRanges($year) as $phase => $range) {
            if ((int) $diagnosis->total_score >= $range['min'] && (int) $diagnosis->total_score <= $range['max']) {
                return 'Ruta '.$phase;
            }
        }

        return self::NO_RECORD;
    }

    public static function maturityLabel(?BusinessDiagnosis $diagnosis): string
    {
        if (! $diagnosis || $diagnosis->total_score === null) {
            return self::NO_RECORD;
        }

        return MaturityScale::getLevelForScore((int) $diagnosis->total_score, $diagnosis->created_at?->year ?? now()->year)['label'];
    }
}
