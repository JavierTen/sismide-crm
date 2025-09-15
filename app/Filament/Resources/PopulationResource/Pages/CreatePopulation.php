<?php

namespace App\Filament\Resources\PopulationResource\Pages;

use App\Filament\Resources\PopulationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePopulation extends CreateRecord
{
    protected static string $resource = PopulationResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createPopulation'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
