<?php

namespace App\Filament\Resources\BusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\BusinessPlanEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessPlanEvaluations extends ListRecords
{
    protected static string $resource = BusinessPlanEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
