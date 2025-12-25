<?php

namespace App\Filament\Resources\EntrepreneurPathResource\Pages;

use App\Filament\Resources\EntrepreneurPathResource;
use Filament\Resources\Pages\ListRecords;

class ListEntrepreneurPaths extends ListRecords
{
    protected static string $resource = EntrepreneurPathResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones por ahora
        ];
    }
}
