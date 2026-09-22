<?php

namespace App\Filament\Eje\Resources\StudentFairParticipationResource\Pages;

use App\Filament\Eje\Resources\StudentFairParticipationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentFairParticipations extends ListRecords
{
    protected static string $resource = StudentFairParticipationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar Participación')
                ->icon('heroicon-o-plus'),
        ];
    }
}
