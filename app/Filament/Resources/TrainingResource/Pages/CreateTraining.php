<?php

namespace App\Filament\Resources\TrainingResource\Pages;

use App\Filament\Resources\TrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTraining extends CreateRecord
{
    protected static string $resource = TrainingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();

        if (! empty($data['start_time']) && ! empty($data['end_time'])) {
            $data['intensity_hours'] = TrainingResource::computeIntensidadPublic(
                $data['start_time'],
                $data['end_time']
            );
        }

        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createTraining'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
