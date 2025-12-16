<?php

namespace App\Filament\Resources\ManagerBusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\ManagerBusinessPlanEvaluationResource;
use Filament\Resources\Pages\ListRecords;

class ListManagerBusinessPlanEvaluations extends ListRecords
{
    protected static string $resource = ManagerBusinessPlanEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones en el header
        ];
    }
}
