<?php

namespace App\Filament\Resources\TrainingSupportResource\Pages;

use App\Filament\Resources\TrainingSupportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingSupports extends ListRecords
{
    protected static string $resource = TrainingSupportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
