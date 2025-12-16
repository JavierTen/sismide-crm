<?php

namespace App\Filament\Resources\ManagerBusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\ManagerBusinessPlanEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManagerBusinessPlanEvaluation extends EditRecord
{
    protected static string $resource = ManagerBusinessPlanEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
