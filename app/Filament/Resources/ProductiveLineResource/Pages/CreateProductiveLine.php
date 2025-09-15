<?php

namespace App\Filament\Resources\ProductiveLineResource\Pages;

use App\Filament\Resources\ProductiveLineResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductiveLine extends CreateRecord
{
    protected static string $resource = ProductiveLineResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createProductiveLine'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
