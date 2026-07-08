<?php

namespace App\Filament\Resources\CharacterizationResource\Pages;

use App\Filament\Resources\CharacterizationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCharacterization extends CreateRecord
{
    protected static string $resource = CharacterizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();

        if (! empty($data['habeas_data_accepted'])) {
            $data['habeas_data_accepted_at'] = now();
            $data['habeas_data_manager_id']  = auth()->id();
        }

        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createCharacterization'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
