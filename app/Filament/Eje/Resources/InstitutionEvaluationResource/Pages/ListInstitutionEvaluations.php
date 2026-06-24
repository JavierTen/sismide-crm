<?php

namespace App\Filament\Eje\Resources\InstitutionEvaluationResource\Pages;

use App\Filament\Eje\Resources\InstitutionEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstitutionEvaluations extends ListRecords
{
    protected static string $resource = InstitutionEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
