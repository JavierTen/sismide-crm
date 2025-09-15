<?php

namespace App\Filament\Resources\EconomicActivityResource\Pages;

use App\Filament\Resources\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEconomicActivity extends CreateRecord
{
    protected static string $resource = EconomicActivityResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createEconomicActivity'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
