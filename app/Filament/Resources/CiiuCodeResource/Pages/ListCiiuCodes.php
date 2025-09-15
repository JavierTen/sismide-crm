<?php

namespace App\Filament\Resources\CiiuCodeResource\Pages;

use App\Filament\Resources\CiiuCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCiiuCodes extends ListRecords
{
    protected static string $resource = CiiuCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createCiiuCode')),
        ];
    }
}
