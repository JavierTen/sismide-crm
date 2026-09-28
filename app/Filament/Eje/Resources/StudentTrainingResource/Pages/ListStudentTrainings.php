<?php

namespace App\Filament\Eje\Resources\StudentTrainingResource\Pages;

use App\Filament\Eje\Resources\StudentTrainingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentTrainings extends ListRecords
{
    protected static string $resource = StudentTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Registrar Capacitación'),
        ];
    }
}
