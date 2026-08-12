<?php

namespace App\Filament\Resources\TrainingParticipationResource\Pages;

use App\Filament\Resources\TrainingParticipationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingParticipations extends ListRecords
{
    protected static string $resource = TrainingParticipationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registrar_masiva')
                ->label('Asistencia masiva')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->url(url('/dashboard/registrar-asistencia'))
                ->visible(fn () => auth()->user()->can('createTrainingParticipation')),

            Actions\CreateAction::make()
                ->label('Asistencia individual')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->visible(false),
        ];
    }
}
