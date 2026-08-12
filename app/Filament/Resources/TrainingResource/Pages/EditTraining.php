<?php

namespace App\Filament\Resources\TrainingResource\Pages;

use App\Filament\Resources\TrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTraining extends EditRecord
{
    protected static string $resource = TrainingResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editTraining'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listTrainings')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteTraining')),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['start_time']) && ! empty($data['end_time'])) {
            $data['intensity_hours'] = TrainingResource::computeIntensidadPublic(
                $data['start_time'],
                $data['end_time']
            );
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
