<?php

namespace App\Filament\Resources\ManagerBusinessPlanEvaluationResource\Pages;

use App\Filament\Resources\ManagerBusinessPlanEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateManagerBusinessPlanEvaluation extends CreateRecord
{
    protected static string $resource = ManagerBusinessPlanEvaluationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
