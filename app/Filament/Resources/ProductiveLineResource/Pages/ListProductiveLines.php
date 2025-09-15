<?php

namespace App\Filament\Resources\ProductiveLineResource\Pages;

use App\Filament\Resources\ProductiveLineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductiveLines extends ListRecords
{
    protected static string $resource = ProductiveLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createProductiveLine')),
        ];
    }
}
