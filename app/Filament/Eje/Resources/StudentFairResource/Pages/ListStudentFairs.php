<?php

namespace App\Filament\Eje\Resources\StudentFairResource\Pages;

use App\Filament\Eje\Resources\StudentFairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentFairs extends ListRecords
{
    protected static string $resource = StudentFairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar Feria')
                ->icon('heroicon-o-plus'),
        ];
    }
}
