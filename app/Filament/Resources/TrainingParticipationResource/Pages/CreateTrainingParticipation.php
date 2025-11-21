<?php

namespace App\Filament\Resources\TrainingParticipationResource\Pages;

use App\Filament\Resources\TrainingParticipationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTrainingParticipation extends CreateRecord
{
    protected static string $resource = TrainingParticipationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createTrainingParticipation'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
