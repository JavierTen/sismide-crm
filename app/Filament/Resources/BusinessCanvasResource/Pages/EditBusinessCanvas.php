<?php

namespace App\Filament\Resources\BusinessCanvasResource\Pages;

use App\Filament\Resources\BusinessCanvasResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditBusinessCanvas extends EditRecord
{
    protected static string $resource = BusinessCanvasResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editBusinessCanvas'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('evaluar_potencial')
                ->label('Evaluar Potencial')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn () => $this->record->is_potential === null)
                ->form([
                    Forms\Components\Section::make('Evaluación de Potencial')
                        ->description('Determina si el emprendedor tiene potencial de crecimiento para ser priorizado en la siguiente etapa.')
                        ->schema([
                            Forms\Components\Radio::make('is_potential')
                                ->label('¿El emprendedor tiene potencial?')
                                ->options([
                                    '1' => 'Sí, tiene potencial',
                                    '0' => 'No, no tiene el potencial requerido',
                                ])
                                ->required()
                                ->inline(false),
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->record->update(['is_potential' => (bool) $data['is_potential']]);
                    $label = (bool) $data['is_potential'] ? 'marcado como potencial' : 'marcado sin potencial';
                    Notification::make()
                        ->success()
                        ->title('Evaluación guardada')
                        ->body("El emprendimiento fue {$label} correctamente.")
                        ->send();
                    $this->redirect($this->getResource()::getUrl('edit', [$this->record->id]));
                }),

            Actions\Action::make('marcar_priorizado')
                ->label(fn () => $this->record->is_prioritized ? 'Quitar prioridad' : 'Marcar priorizado')
                ->icon('heroicon-o-bookmark')
                ->color(fn () => $this->record->is_prioritized ? 'gray' : 'warning')
                ->visible(fn () => $this->record->is_potential === true || $this->record->is_potential == 1)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['is_prioritized' => ! $this->record->is_prioritized]);
                    Notification::make()
                        ->success()
                        ->title($this->record->is_prioritized ? 'Emprendedor priorizado' : 'Prioridad removida')
                        ->send();
                }),

            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
