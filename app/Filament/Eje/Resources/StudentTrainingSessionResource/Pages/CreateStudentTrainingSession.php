<?php

namespace App\Filament\Eje\Resources\StudentTrainingSessionResource\Pages;

use App\Filament\Eje\Resources\StudentTrainingSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentTrainingSession extends CreateRecord
{
    protected static string $resource = StudentTrainingSessionResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createStudentTrainingSession'), 403);

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();

        return $data;
    }

    /**
     * La asistencia no es una columna: se guarda en la pivote conservando el
     * listado completo de convocados, marcados y no marcados.
     */
    protected function afterCreate(): void
    {
        $this->record->syncAttendance($this->data['attending_students'] ?? []);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
