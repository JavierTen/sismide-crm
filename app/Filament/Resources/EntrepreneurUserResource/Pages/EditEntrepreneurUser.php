<?php

namespace App\Filament\Resources\EntrepreneurUserResource\Pages;

use App\Filament\Resources\EntrepreneurUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEntrepreneurUser extends EditRecord
{
    protected static string $resource = EntrepreneurUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
