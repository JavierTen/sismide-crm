<?php

namespace App\Filament\Emprendedor\Resources\PqrfResource\Pages;

use App\Filament\Emprendedor\Resources\PqrfResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPqrves extends ListRecords
{
    protected static string $resource = PqrfResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
