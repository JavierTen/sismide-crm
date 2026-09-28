<?php

namespace App\Filament\Eje\Resources\StudentTrainingSessionResource\Pages;

use App\Filament\Eje\Resources\StudentTrainingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentTrainingSession extends EditRecord
{
    protected static string $resource = StudentTrainingSessionResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editStudentTrainingSession'), 403);

        parent::mount($record);
    }

    /** Precarga el checklist con quienes constan como asistentes. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['attending_students'] = $this->record
            ->attendees()
            ->pluck('students.id')
            ->all();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncAttendance($this->data['attending_students'] ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
