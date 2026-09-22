<?php

namespace App\Filament\Eje\Resources\StudentFairResource\Pages;

use App\Filament\Eje\Resources\StudentFairResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentFair extends CreateRecord
{
    protected static string $resource = StudentFairResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createStudentFair'), 403);
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
