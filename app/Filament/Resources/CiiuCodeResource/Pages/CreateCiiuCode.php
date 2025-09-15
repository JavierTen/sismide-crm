<?php

namespace App\Filament\Resources\CiiuCodeResource\Pages;

use App\Filament\Resources\CiiuCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCiiuCode extends CreateRecord
{
    protected static string $resource = CiiuCodeResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createCiiuCode'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
