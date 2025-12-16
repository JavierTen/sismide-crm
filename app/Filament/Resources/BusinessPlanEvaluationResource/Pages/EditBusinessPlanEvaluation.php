<?php

namespace App\Filament\Resources\BusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\BusinessPlanEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessPlanEvaluation extends EditRecord
{
    protected static string $resource = BusinessPlanEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
