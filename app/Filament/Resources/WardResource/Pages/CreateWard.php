<?php

namespace App\Filament\Resources\WardResource\Pages;

use App\Filament\Resources\WardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWard extends CreateRecord
{
    protected static string $resource = WardResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createWard'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
