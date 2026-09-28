<?php

namespace App\Filament\Eje\Resources\StudentTrainingSessionResource\Pages;

use App\Filament\Eje\Resources\StudentTrainingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentTrainingSessions extends ListRecords
{
    protected static string $resource = StudentTrainingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Registrar Sesión'),
        ];
    }
}
