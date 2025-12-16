<?php

namespace App\Filament\Resources\EvaluationResultResource\Pages;

use App\Filament\Resources\EvaluationResultResource;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationResults extends ListRecords
{
    protected static string $resource = EvaluationResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones
        ];
    }
}
