<?php

namespace App\Filament\Resources\PopulationResource\Pages;

use App\Filament\Resources\PopulationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPopulation extends EditRecord
{
    protected static string $resource = PopulationResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editPopulation'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listPopulations')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deletePopulation')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
