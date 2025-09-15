<?php

namespace App\Filament\Resources\CharacterizationResource\Pages;

use App\Filament\Resources\CharacterizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCharacterizations extends ListRecords
{
    protected static string $resource = CharacterizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createCharacterization')),
        ];
    }
}
