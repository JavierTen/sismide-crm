<?php

namespace App\Filament\Resources\FairResource\Pages;

use App\Filament\Resources\FairResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFair extends CreateRecord
{
    protected static string $resource = FairResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createFair'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
