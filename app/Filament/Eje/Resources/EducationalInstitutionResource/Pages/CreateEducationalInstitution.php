<?php

namespace App\Filament\Eje\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Eje\Resources\EducationalInstitutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationalInstitution extends CreateRecord
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
