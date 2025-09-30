<?php

namespace App\Filament\Resources\BusinessDiagnosisResource\Pages;

use App\Filament\Resources\BusinessDiagnosisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessDiagnosis extends CreateRecord
{
    protected static string $resource = BusinessDiagnosisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createBusinessDiagnosis'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
