<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateMaritalStatus extends CreateRecord
{
    protected static string $resource = MaritalStatusResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createMaritalStatus'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
