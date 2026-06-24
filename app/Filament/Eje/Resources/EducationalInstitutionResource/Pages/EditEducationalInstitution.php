<?php

namespace App\Filament\Eje\Resources\EducationalInstitutionResource\Pages;

use App\Filament\Eje\Resources\EducationalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationalInstitution extends EditRecord
{
    protected static string $resource = EducationalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
