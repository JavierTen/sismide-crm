<?php

namespace App\Filament\Eje\Pages;

use App\Models\City;
use App\Models\EducationalInstitution;
use App\Models\StudentFair;
use App\Support\EjeAlerts;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\View\View;

class Alerts extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Alertas';
    protected static ?string $title           = 'Alertas y Control de Calidad';
    protected static ?int    $navigationSort  = 2;

    protected static string $view = 'filament.eje.pages.alerts';

    /** @var array<string, mixed> */
    public ?array $filters = [
        'city_id'                    => null,
        'educational_institution_id' => null,
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewEjeAlerts') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->form->fill($this->filters);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('filters')
            ->columns(2)
            ->schema([
                Select::make('city_id')
                    ->label('Municipio')
                    ->options(fn () => City::whereIn('id', StudentFair::ALLOWED_CITY_IDS)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
                    ->placeholder('Todos los municipios')
                    ->live()
                    // Cambiar de municipio invalida la institución elegida.
                    ->afterStateUpdated(fn (Set $set) => $set('educational_institution_id', null)),

                Select::make('educational_institution_id')
                    ->label('Institución educativa')
                    ->options(fn (Get $get) => EducationalInstitution::query()
                        ->when(filled($get('city_id')), fn ($query) => $query->where('city_id', $get('city_id')))
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn ($institution) => [$institution->id => $institution->display_name]))
                    ->placeholder('Todas las instituciones')
                    ->searchable()
                    ->live(),
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAlerts(): array
    {
        return EjeAlerts::summary($this->filters ?? []);
    }

    /**
     * Las alertas se pintan con la misma tarjeta que los indicadores, para que
     * el panel se vea de una pieza. Toda la tarjeta abre el detalle.
     *
     * @return array<int, Stat>
     */
    public function getAlertStats(): array
    {
        return array_map(
            fn (array $alert): Stat => Stat::make($alert['label'], number_format($alert['count']))
                ->description($alert['description'])
                ->descriptionIcon($alert['icon'])
                ->color('warning')
                ->extraAttributes([
                    'class'      => 'cursor-pointer transition hover:ring-2 hover:ring-warning-400',
                    'title'      => 'Ver detalle',
                    'wire:click' => "mountAction('detail', { key: '{$alert['key']}' })",
                ]),
            $this->getAlerts(),
        );
    }

    /** Detalle de una alerta, montado desde la tarjeta correspondiente. */
    public function detailAction(): Action
    {
        return Action::make('detail')
            ->modalHeading(fn (array $arguments): string => EjeAlerts::label($arguments['key'] ?? ''))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalWidth('3xl')
            ->modalContent(function (array $arguments): View {
                $key = $arguments['key'] ?? '';

                return view('filament.eje.pages.alert-detail', [
                    'key'     => $key,
                    'records' => EjeAlerts::query($key, $this->filters ?? [])
                        ->limit(500)
                        ->get(),
                ]);
            });
    }

    public function clearFiltersAction(): Action
    {
        return Action::make('clearFilters')
            ->label('Limpiar filtros')
            ->color('gray')
            ->link()
            ->action(fn () => $this->form->fill([
                'city_id'                    => null,
                'educational_institution_id' => null,
            ]));
    }
}
