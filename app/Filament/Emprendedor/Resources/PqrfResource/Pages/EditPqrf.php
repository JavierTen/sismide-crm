<?php

namespace App\Filament\Emprendedor\Resources\PqrfResource\Pages;

use App\Filament\Emprendedor\Resources\PqrfResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPqrf extends EditRecord
{
    protected static string $resource = PqrfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn ($record) => $record->isClosed()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'PQRF actualizada exitosamente';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // No permitir cambiar estos campos
        unset($data['entrepreneur_id']);
        unset($data['manager_id']);
        unset($data['status']);
        unset($data['response']);
        unset($data['response_date']);
        unset($data['response_files']);
        unset($data['responded_by']);

        return $data;
    }
}
