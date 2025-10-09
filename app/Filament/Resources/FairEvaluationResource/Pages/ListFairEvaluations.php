<?php

namespace App\Filament\Resources\FairEvaluationResource\Pages;

use App\Filament\Resources\FairEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFairEvaluations extends ListRecords
{
    protected static string $resource = FairEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createFairParticipation')),
        ];
    }
}
