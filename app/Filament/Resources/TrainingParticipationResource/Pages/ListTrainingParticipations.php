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
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createTrainingParticipation')),
        ];
    }
}
