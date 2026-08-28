<?php

namespace App\Filament\Resources\BusinessCanvasResource\Pages;

use App\Filament\Resources\BusinessCanvasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessCanvas extends CreateRecord
{
    protected static string $resource = BusinessCanvasResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createBusinessCanvas'), 403);
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
