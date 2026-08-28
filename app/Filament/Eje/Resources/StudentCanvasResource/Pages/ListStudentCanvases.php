<?php

namespace App\Filament\Eje\Resources\StudentCanvasResource\Pages;

use App\Filament\Eje\Resources\StudentCanvasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentCanvases extends ListRecords
{
    protected static string $resource = StudentCanvasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Registrar Canvas')
                ->icon('heroicon-o-plus'),
        ];
    }
}
