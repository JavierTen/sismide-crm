<?php

namespace App\Filament\Resources\TrainingSupportResource\Pages;

use App\Filament\Resources\TrainingSupportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingSupport extends EditRecord
{
    protected static string $resource = TrainingSupportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
