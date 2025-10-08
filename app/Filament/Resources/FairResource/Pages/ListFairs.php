<?php

namespace App\Filament\Resources\FairResource\Pages;

use App\Filament\Resources\FairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFairs extends ListRecords
{
    protected static string $resource = FairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createFair')),
        ];
    }
}
