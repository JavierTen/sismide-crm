<?php

namespace App\Filament\Eje\Resources\InstitutionEvaluationResource\Pages;

use App\Filament\Eje\Resources\InstitutionEvaluationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitutionEvaluation extends CreateRecord
{
    protected static string $resource = InstitutionEvaluationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
