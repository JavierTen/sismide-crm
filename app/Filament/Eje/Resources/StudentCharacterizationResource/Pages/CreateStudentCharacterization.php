<?php

namespace App\Filament\Eje\Resources\StudentCharacterizationResource\Pages;

use App\Filament\Eje\Resources\StudentCharacterizationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentCharacterization extends CreateRecord
{
    protected static string $resource = StudentCharacterizationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
