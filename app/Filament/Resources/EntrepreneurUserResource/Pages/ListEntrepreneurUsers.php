<?php

namespace App\Filament\Resources\EntrepreneurUserResource\Pages;

use App\Filament\Resources\EntrepreneurUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntrepreneurUsers extends ListRecords
{
    protected static string $resource = EntrepreneurUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
