<?php

namespace App\Filament\Eje\Resources\StudentTrainingResource\Pages;

use App\Filament\Eje\Resources\StudentTrainingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentTraining extends CreateRecord
{
    protected static string $resource = StudentTrainingResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createStudentTraining'), 403);

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
