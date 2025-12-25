<?php

namespace App\Filament\Resources\EntrepreneurPathResource\Pages;

use App\Filament\Resources\EntrepreneurPathResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageEntrepreneurPaths extends ManageRecords
{
    protected static string $resource = EntrepreneurPathResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
