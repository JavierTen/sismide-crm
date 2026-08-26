<?php

namespace App\Filament\Resources\InstitutionalDocumentResource\Pages;

use App\Filament\Resources\InstitutionalDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitutionalDocument extends CreateRecord
{
    protected static string $resource = InstitutionalDocumentResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createInstitutionalDocument'), 403);
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
