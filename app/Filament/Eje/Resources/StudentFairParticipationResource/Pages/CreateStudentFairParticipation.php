<?php

namespace App\Filament\Eje\Resources\StudentFairParticipationResource\Pages;

use App\Filament\Eje\Resources\StudentFairParticipationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentFairParticipation extends CreateRecord
{
    protected static string $resource = StudentFairParticipationResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createStudentFairParticipation'), 403);
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
