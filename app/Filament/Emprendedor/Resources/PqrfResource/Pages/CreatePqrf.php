<?php

namespace App\Filament\Emprendedor\Resources\PqrfResource\Pages;

use App\Filament\Emprendedor\Resources\PqrfResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePqrf extends CreateRecord
{
    protected static string $resource = PqrfResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $entrepreneur = auth()->user();

        $data['entrepreneur_id'] = $entrepreneur->id;
        $data['manager_id'] = $entrepreneur->manager_id;
        $data['status'] = 'pending';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'PQRF registrada exitosamente';
    }
}
