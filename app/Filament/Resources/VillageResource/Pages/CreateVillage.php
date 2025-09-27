<?php

namespace App\Filament\Resources\VillageResource\Pages;

use App\Filament\Resources\VillageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVillage extends CreateRecord
{
    protected static string $resource = VillageResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createVillage'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
