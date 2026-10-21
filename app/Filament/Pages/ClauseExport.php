<?php

namespace App\Filament\Pages;

use App\Exports\ClauseExcelExport;
use App\Models\Entrepreneur;
use App\Models\ExportTemplate;
use App\Models\Gender;
use App\Models\Population;
use App\Models\User;
use App\Support\ClauseExport\ClauseExportBuilder;
use App\Support\ClauseExport\Clauses;
use App\Support\ClauseExport\FieldCatalog;
use App\Support\ExportLogger;
use App\Support\YearContext;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exportación por cláusulas: un solo Excel que cruza la información de todos
 * los módulos, con las columnas que cada usuario escoja.
 */
class ClauseExport extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-table-cells';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Exportación por cláusulas';
    protected static ?string $title           = 'Exportación por cláusulas';
    protected static ?int    $navigationSort  = 1;

    protected static string $view = 'filament.pages.clause-export';

    /** Filas de la vista previa. */
    public const PREVIEW_ROWS = 10;

    /** Columnas con las que arranca la pantalla: el ejemplo del documento. */
    private const DEFAULT_COLUMNS = ['full_name', 'business_name', 'city', 'manager'];

    public string $clause = Clauses::LA_LOMA;

    /** @var array<int> */
    public array $excludedCities = [];

    /** @var array<string> claves del catálogo, en el orden del Excel */
    public array $columns = self::DEFAULT_COLUMNS;

    public string $search = '';

    public ?string $templateId = null;

    /** Año de la exportación; se escoge junto a la plantilla. */
    public int $year;

    /** Condiciones opcionales (formulario de Filament). */
    public ?array $filters = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('exportByClauses') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->year = YearContext::effectiveYear() ?? now()->year;
        $this->form->fill(['conditions' => []]);
    }

    /** @return array<int, int> años con datos, del más reciente al más antiguo */
    public function yearOptions(): array
    {
        return collect(YearContext::availableYears('dashboard'))->map(fn ($y) => (int) $y)->sortDesc()->values()->all();
    }

    public function updatedYear(): void
    {
        if (! in_array((int) $this->year, $this->yearOptions(), true)) {
            $this->year = now()->year;
        }
    }

    // ── Paso 1: cláusula y municipios ─────────────────────────────────────

    public function selectClause(string $clause): void
    {
        if (! array_key_exists($clause, Clauses::options())) {
            return;
        }

        $this->clause = $clause;
        $this->excludedCities = [];
    }

    public function toggleCity(int $cityId): void
    {
        $this->excludedCities = in_array($cityId, $this->excludedCities, true)
            ? array_values(array_diff($this->excludedCities, [$cityId]))
            : [...$this->excludedCities, $cityId];
    }

    // ── Paso 2: columnas ──────────────────────────────────────────────────

    public function toggleColumn(string $key): void
    {
        if (! FieldCatalog::get($key)) {
            return;
        }

        $this->columns = in_array($key, $this->columns, true)
            ? array_values(array_diff($this->columns, [$key]))
            : [...$this->columns, $key];
    }

    public function toggleGroup(string $group): void
    {
        $keys = array_column(FieldCatalog::groups()[$group] ?? [], 'key');
        $all  = array_diff($keys, $this->columns) === [];

        $this->columns = $all
            ? array_values(array_diff($this->columns, $keys))
            : array_values(array_unique([...$this->columns, ...$keys]));
    }

    public function moveColumn(int $index, int $direction): void
    {
        $target = $index + $direction;

        if (! isset($this->columns[$index], $this->columns[$target])) {
            return;
        }

        [$this->columns[$index], $this->columns[$target]] = [$this->columns[$target], $this->columns[$index]];
    }

    // ── Año y condiciones ─────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        $yesNo = ['1' => 'Sí', '0' => 'No'];

        return $form
            ->statePath('filters')
            ->schema([
                Forms\Components\Section::make('Condiciones opcionales')
                    ->description('Para afinar el resultado. Sin condiciones salen todos los emprendedores de la cláusula.')
                    ->collapsible()
                    ->collapsed()
                    ->columns(3)
                    ->statePath('conditions')
                    ->schema([
                        Forms\Components\Select::make('routes')
                            ->label('Ruta')
                            ->multiple()
                            ->options(['route_1' => 'Ruta 1', 'route_2' => 'Ruta 2', 'route_3' => 'Ruta 3'])
                            ->live(),

                        Forms\Components\Select::make('genders')
                            ->label('Género')
                            ->multiple()
                            ->options(fn () => Gender::orderBy('name')->pluck('name', 'id'))
                            ->live(),

                        Forms\Components\Select::make('populations')
                            ->label('Población vulnerable')
                            ->multiple()
                            ->searchable()
                            ->options(fn () => Population::orderBy('name')->pluck('name', 'id'))
                            ->live(),

                        Forms\Components\Select::make('managers')
                            ->label('Gestor')
                            ->multiple()
                            ->searchable()
                            ->options(fn () => User::whereIn('id', Entrepreneur::withoutGlobalScopes()->distinct()->pluck('manager_id'))->orderBy('name')->pluck('name', 'id'))
                            ->live(),

                        Forms\Components\Select::make('has_characterization')->label('¿Tiene caracterización?')->options($yesNo)->placeholder('Indiferente')->live(),
                        Forms\Components\Select::make('has_entry_diagnosis')->label('¿Tiene diagnóstico de entrada?')->options($yesNo)->placeholder('Indiferente')->live(),
                        Forms\Components\Select::make('has_exit_diagnosis')->label('¿Tiene diagnóstico de salida?')->options($yesNo)->placeholder('Indiferente')->live(),
                        Forms\Components\Select::make('has_plan')->label('¿Tiene plan de negocio?')->options($yesNo)->placeholder('Indiferente')->live(),
                        Forms\Components\Select::make('has_canvas')->label('¿Tiene Modelo Canvas?')->options($yesNo)->placeholder('Indiferente')->live(),

                        Forms\Components\Select::make('plan_document')
                            ->label('Documento del plan')
                            ->options(ClauseExportBuilder::PLAN_DOCUMENTS)
                            ->placeholder('Ninguno')
                            ->live(),

                        Forms\Components\Select::make('plan_document_attached')
                            ->label('¿Lo adjuntó?')
                            ->options($yesNo)
                            ->placeholder('Indiferente')
                            ->helperText('"No" incluye a quien aún no tiene plan.')
                            ->disabled(fn (Get $get): bool => blank($get('plan_document')))
                            ->live(),
                    ]),
            ]);
    }

    // ── Estado derivado ───────────────────────────────────────────────────

    /** @return array<string, mixed> */
    public function config(): array
    {
        return [
            'clause'          => $this->clause,
            'excluded_cities' => $this->excludedCities,
            'year'            => (int) $this->year,
            'columns'         => $this->columns,
            'conditions'      => $this->filters['conditions'] ?? [],
        ];
    }

    public function builder(): ClauseExportBuilder
    {
        return ClauseExportBuilder::fromConfig($this->config());
    }

    /** Grupos del catálogo filtrados por el buscador (sin distinguir tildes). */
    public function visibleGroups(): array
    {
        $needle = Str::of($this->search)->ascii()->lower()->trim()->value();

        if ($needle === '') {
            return FieldCatalog::groups();
        }

        $groups = [];

        foreach (FieldCatalog::groups() as $group => $fields) {
            $groupMatches = str_contains(Str::of($group)->ascii()->lower()->value(), $needle);

            $matching = array_values(array_filter($fields, fn ($field) => $groupMatches
                || str_contains(Str::of($field['label'])->ascii()->lower()->value(), $needle)));

            if ($matching !== []) {
                $groups[$group] = $matching;
            }
        }

        return $groups;
    }

    /** @return array<string, array<int, ExportTemplate>> */
    public function templateOptions(): array
    {
        $templates = ExportTemplate::query()->visibleTo(auth()->user())->with('user')->orderBy('name')->get();

        return [
            'Mis plantillas' => $templates->filter(fn ($t) => $t->isOwnedBy(auth()->user()))->values()->all(),
            'Públicas'       => $templates->reject(fn ($t) => $t->isOwnedBy(auth()->user()))->values()->all(),
        ];
    }

    public function currentTemplate(): ?ExportTemplate
    {
        return $this->templateId
            ? ExportTemplate::query()->visibleTo(auth()->user())->find($this->templateId)
            : null;
    }

    // ── Plantillas ────────────────────────────────────────────────────────

    public function updatedTemplateId(): void
    {
        $template = $this->currentTemplate();

        if (! $template) {
            $this->templateId = null;

            return;
        }

        $config = $template->config;

        $this->clause         = array_key_exists($config['clause'] ?? '', Clauses::options()) ? $config['clause'] : Clauses::LA_LOMA;
        $this->excludedCities = array_map('intval', $config['excluded_cities'] ?? []);
        $this->columns        = array_values(array_filter($config['columns'] ?? [], fn ($key) => FieldCatalog::get($key) !== null));

        // El año no se guarda en la plantilla: un informe mensual se reusa el
        // año siguiente sin tener que editarlo.
        $this->form->fill(['conditions' => $config['conditions'] ?? []]);

        Notification::make()->title("Plantilla \"{$template->name}\" cargada")->success()->send();
    }

    public function saveTemplateAction(): Action
    {
        return Action::make('saveTemplate')
            ->label('Guardar como plantilla')
            ->icon('heroicon-o-bookmark')
            ->color('gray')
            ->disabled(fn (): bool => $this->columns === [])
            ->modalHeading('Guardar como plantilla')
            ->modalDescription('Guarda la cláusula, los municipios, las columnas y las condiciones. El año no se guarda.')
            ->form([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->placeholder('Ej: Informe mensual La Loma')
                    ->required()
                    ->maxLength(120)
                    ->rule(fn () => Rule::unique('export_templates', 'name')->where('user_id', auth()->id()))
                    ->validationMessages(['unique' => 'Ya tiene una plantilla con este nombre.']),

                Forms\Components\Radio::make('visibility')
                    ->label('¿Quién la puede ver?')
                    ->options([
                        ExportTemplate::VISIBILITY_PRIVATE => 'Privada: solo yo',
                        ExportTemplate::VISIBILITY_PUBLIC  => 'Pública: todos los que usan este módulo (solo yo puedo editarla)',
                    ])
                    ->default(ExportTemplate::VISIBILITY_PRIVATE)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $template = ExportTemplate::create([
                    'user_id'    => auth()->id(),
                    'name'       => $data['name'],
                    'visibility' => $data['visibility'],
                    'config'     => $this->templateConfig(),
                ]);

                $this->templateId = (string) $template->getKey();

                Notification::make()->title('Plantilla guardada')->success()->send();
            });
    }

    public function updateTemplateAction(): Action
    {
        return Action::make('updateTemplate')
            ->label('Actualizar plantilla')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->visible(fn (): bool => (bool) $this->currentTemplate()?->isOwnedBy(auth()->user()))
            ->requiresConfirmation()
            ->modalHeading(fn (): string => 'Actualizar "'.$this->currentTemplate()?->name.'"')
            ->modalDescription('Se reemplazará su configuración por la que está en pantalla.')
            ->form([
                Forms\Components\Radio::make('visibility')
                    ->label('¿Quién la puede ver?')
                    ->options([
                        ExportTemplate::VISIBILITY_PRIVATE => 'Privada: solo yo',
                        ExportTemplate::VISIBILITY_PUBLIC  => 'Pública: todos los que usan este módulo',
                    ])
                    ->default(fn () => $this->currentTemplate()?->visibility)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $template = $this->currentTemplate();

                if (! $template?->isOwnedBy(auth()->user())) {
                    return;
                }

                $template->update(['visibility' => $data['visibility'], 'config' => $this->templateConfig()]);

                Notification::make()->title('Plantilla actualizada')->success()->send();
            });
    }

    public function deleteTemplateAction(): Action
    {
        return Action::make('deleteTemplate')
            ->label('Eliminar plantilla')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->link()
            ->visible(fn (): bool => (bool) $this->currentTemplate()?->isOwnedBy(auth()->user()))
            ->requiresConfirmation()
            ->modalHeading(fn (): string => 'Eliminar "'.$this->currentTemplate()?->name.'"')
            ->modalDescription(fn (): string => $this->currentTemplate()?->visibility === ExportTemplate::VISIBILITY_PUBLIC
                ? 'Es pública: dejará de estar disponible para todos.'
                : 'Esta acción no se puede deshacer.')
            ->action(function (): void {
                $template = $this->currentTemplate();

                if (! $template?->isOwnedBy(auth()->user())) {
                    return;
                }

                $template->delete();
                $this->templateId = null;

                Notification::make()->title('Plantilla eliminada')->success()->send();
            });
    }

    /** Configuración que se guarda: todo menos el año. */
    private function templateConfig(): array
    {
        $config = $this->config();
        unset($config['year']);

        return $config;
    }

    // ── Paso 3: exportar ──────────────────────────────────────────────────

    public function exportAction(): Action
    {
        return Action::make('export')
            ->label('Exportar Excel')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->disabled(fn (): bool => $this->columns === [] || $this->builder()->cityIds() === [])
            ->action(function () {
                $builder = $this->builder();
                $rows    = $builder->rows();

                $suffix   = ['la_loma' => 'LaLoma', 'el_corozo' => 'ElCorozo', 'both' => 'Ambas'][$this->clause] ?? 'Clausulas';
                $filename = sprintf('RutaD_%s_%d_%s.xlsx', $suffix, $builder->year, now()->format('Y-m-d-His'));

                // Ley 1581 de 2012: queda constancia de quién descargó qué.
                ExportLogger::log('Exportación por cláusulas', [
                    'file'    => $filename,
                    'records' => count($rows),
                    ...$builder->describe(),
                    'template' => $this->currentTemplate()?->name,
                ]);

                return Excel::download(new ClauseExcelExport($builder->headings(), $rows), $filename);
            });
    }
}
