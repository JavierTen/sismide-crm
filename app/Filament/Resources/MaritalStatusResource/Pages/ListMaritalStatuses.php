<?php

namespace App\Filament\Resources\MaritalStatusResource\Pages;

use App\Filament\Resources\MaritalStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaritalStatuses extends ListRecords
{
    protected static string $resource = MaritalStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createMaritalStatus')),
        ];
    }
}
