<?php

namespace App\Filament\Eje\Resources\StudentCharacterizationResource\Pages;

use App\Filament\Eje\Resources\StudentCharacterizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentCharacterizations extends ListRecords
{
    protected static string $resource = StudentCharacterizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
