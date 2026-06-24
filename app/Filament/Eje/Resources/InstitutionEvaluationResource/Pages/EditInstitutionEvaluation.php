<?php

namespace App\Filament\Eje\Resources\InstitutionEvaluationResource\Pages;

use App\Filament\Eje\Resources\InstitutionEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionEvaluation extends EditRecord
{
    protected static string $resource = InstitutionEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
