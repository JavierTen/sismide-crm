<?php

namespace App\Filament\Resources\ActorResource\Pages;

use App\Filament\Resources\ActorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewActor extends ViewRecord
{
    protected static string $resource = ActorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->visible(fn() => ActorResource::canEdit($this->getRecord())),
        ];
    }
}
