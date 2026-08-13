<?php

namespace App\Filament\Pages;

use App\Filament\Resources\TrainingParticipationResource;
use App\Models\Entrepreneur;
use App\Models\Training;
use App\Models\TrainingParticipation;
use App\Models\TrainingSession;
use App\Support\MaturityScale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RegistrarAsistencia extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Capacitaciones';
    protected static ?string $navigationLabel = 'Registrar Asistencia';
    protected static ?string $title           = 'Registrar Asistencia Masiva';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.registrar-asistencia';

    // Datos del formulario de selección
    public ?array $data = [];

    // Estado del checklist
    public bool   $loaded       = false;
    public array  $entrepreneurs = [];  // [{id, name, business}]
    public array  $attendees     = [];  // [id => bool]
    public string $search        = '';
    public bool   $selectAll     = true;

    public static function canAccess(): bool
    {
        return auth()->user()->can('createTrainingParticipation');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('training_id')
                    ->label('Capacitación')
                    ->options(fn () => Training::with('city')->orderBy('name')->get()
                        ->mapWithKeys(fn ($t) => [
                            $t->id => trim($t->name . ($t->city?->name ? ' - ' . $t->city->name : '') . ($t->training_date ? ' (' . $t->training_date->format('d/m/Y') . ')' : '')),
                        ])->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $this->resetChecklist();
                        $training = $state ? Training::find($state) : null;
                        $set('city_id', $training?->city_id);
                    }),

                Hidden::make('city_id'),

                Placeholder::make('route_display')
                    ->label('Ruta')
                    ->content(function ($get) {
                        $training = Training::find($get('training_id'));
                        return match ($training?->route) {
                            'route_1' => 'Ruta 1: Pre-emprendimiento',
                            'route_2' => 'Ruta 2: Consolidación',
                            'route_3' => 'Ruta 3: Escalamiento e Innovación',
                            default   => '—',
                        };
                    })
                    ->visible(fn ($get) => (bool) $get('training_id')),

                DatePicker::make('session_date')
                    ->label('Fecha de Realización')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live()
                    ->visible(fn ($get) => (bool) $get('training_id'))
                    ->afterStateUpdated(function () {
                        $this->resetChecklist();
                    }),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function loadEntrepreneurs(): void
    {
        $trainingId = $this->data['training_id'] ?? null;
        $cityId     = $this->data['city_id']     ?? null;
        $date       = $this->data['session_date'] ?? null;

        if (! $trainingId || ! $cityId || ! $date) {
            Notification::make()->warning()->title('Completa todos los campos antes de cargar.')->send();
            return;
        }

        $training = Training::find($trainingId);
        if (! $training) {
            return;
        }

        // Verificar que no exista ya una sesión para esta capacitación en esta fecha
        $exists = TrainingSession::withTrashed()
            ->where('training_id', $trainingId)
            ->where('session_date', $date)
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Ya existe una sesión registrada para esta capacitación en esta fecha.')
                ->body('No es posible registrar dos sesiones de la misma capacitación el mismo día.')
                ->send();
            return;
        }

        $entrepreneurs = Entrepreneur::where('city_id', $cityId)
            ->whereHas('characterizations')
            ->whereHas('businessDiagnoses')
            ->with([
                'business',
                'businessDiagnoses' => fn ($q) => $q->latest(),
            ])
            ->get()
            ->filter(fn ($e) => $this->getRouteForEntrepreneur($e) === $training->route)
            ->values();

        if ($entrepreneurs->isEmpty()) {
            Notification::make()
                ->warning()
                ->title('No hay emprendedores habilitados para esta Ruta y municipio.')
                ->send();
            return;
        }

        $this->entrepreneurs = $entrepreneurs->map(fn ($e) => [
            'id'       => $e->id,
            'name'     => $e->full_name,
            'business' => $e->business?->business_name ?? '—',
        ])->toArray();

        // Por defecto todos marcados como asistentes
        $this->attendees = [];
        foreach ($this->entrepreneurs as $e) {
            $this->attendees[$e['id']] = true;
        }

        $this->selectAll = true;
        $this->loaded    = true;
    }

    public function toggleAll(): void
    {
        $this->selectAll = ! $this->selectAll;
        foreach ($this->entrepreneurs as $e) {
            $this->attendees[$e['id']] = $this->selectAll;
        }
    }

    public function saveAttendance(): void
    {
        if (! $this->loaded || empty($this->entrepreneurs)) {
            Notification::make()->warning()->title('Carga primero la lista de emprendedores.')->send();
            return;
        }

        $trainingId = $this->data['training_id'] ?? null;
        $cityId     = $this->data['city_id']     ?? null;
        $date       = $this->data['session_date'] ?? null;

        if (! $trainingId || ! $cityId || ! $date) {
            return;
        }

        $training = Training::find($trainingId);

        // Segunda guarda antes del insert: misma validación sin city_id
        $sessionExists = TrainingSession::withTrashed()
            ->where('training_id', $trainingId)
            ->where('session_date', $date)
            ->exists();

        if ($sessionExists) {
            Notification::make()
                ->danger()
                ->title('Ya existe una sesión registrada para esta capacitación en esta fecha.')
                ->body('No es posible registrar dos sesiones de la misma capacitación el mismo día.')
                ->send();
            return;
        }

        DB::transaction(function () use ($trainingId, $cityId, $date, $training) {
            $session = TrainingSession::create([
                'training_id'  => $trainingId,
                'city_id'      => $cityId,
                'session_date' => $date,
                'manager_id'   => auth()->id(),
            ]);

            foreach ($this->entrepreneurs as $entrepreneur) {
                $attended = (bool) ($this->attendees[$entrepreneur['id']] ?? false);

                TrainingParticipation::create([
                    'training_id'         => $trainingId,
                    'training_session_id' => $session->id,
                    'entrepreneur_id'     => $entrepreneur['id'],
                    'manager_id'          => auth()->id(),
                    'attended'            => $attended,
                    'route_snapshot'      => $training?->route,
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title('Asistencia registrada correctamente.')
            ->body(count($this->entrepreneurs) . ' registros guardados.')
            ->send();

        $this->redirect(TrainingParticipationResource::getUrl('index'));
    }

    public function getRouteLabel(): string
    {
        $trainingId = $this->data['training_id'] ?? null;
        if (! $trainingId) {
            return '';
        }

        $training = Training::find($trainingId);
        return match ($training?->route) {
            'route_1' => 'Ruta 1: Pre-emprendimiento',
            'route_2' => 'Ruta 2: Consolidación',
            'route_3' => 'Ruta 3: Escalamiento e Innovación',
            default   => $training?->route ?? '',
        };
    }

    public function getAttendedCount(): int
    {
        return count(array_filter($this->attendees));
    }

    public function getFilteredEntrepreneurs(): array
    {
        if (! $this->search) {
            return $this->entrepreneurs;
        }

        $q = mb_strtolower($this->search);

        return array_values(array_filter(
            $this->entrepreneurs,
            fn ($e) => str_contains(mb_strtolower($e['name']), $q)
                    || str_contains(mb_strtolower($e['business']), $q)
        ));
    }

    public function resetSearch(): void
    {
        $this->resetChecklist();
        $this->form->fill();
    }

    private function resetChecklist(): void
    {
        $this->loaded        = false;
        $this->entrepreneurs = [];
        $this->attendees     = [];
        $this->search        = '';
        $this->selectAll     = true;
    }

    private function getRouteForEntrepreneur(Entrepreneur $e): ?string
    {
        $diagnosis = $e->businessDiagnoses->first();

        if (! $diagnosis || $diagnosis->total_score === null) {
            return null;
        }

        $year   = $diagnosis->created_at?->year ?? now()->year;
        $phases = MaturityScale::getPhaseRanges($year);
        $score  = (int) $diagnosis->total_score;

        foreach ($phases as $phase => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return 'route_' . $phase;
            }
        }

        return null;
    }
}
