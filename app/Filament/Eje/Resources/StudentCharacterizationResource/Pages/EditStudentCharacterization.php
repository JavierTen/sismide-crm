<?php

namespace App\Filament\Eje\Resources\StudentCharacterizationResource\Pages;

use App\Filament\Eje\Resources\StudentCharacterizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentCharacterization extends EditRecord
{
    protected static string $resource = StudentCharacterizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
