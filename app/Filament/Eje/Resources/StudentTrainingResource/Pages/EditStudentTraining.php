<?php

namespace App\Filament\Eje\Resources\StudentTrainingResource\Pages;

use App\Filament\Eje\Resources\StudentTrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentTraining extends EditRecord
{
    protected static string $resource = StudentTrainingResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editStudentTraining'), 403);

        parent::mount($record);
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
