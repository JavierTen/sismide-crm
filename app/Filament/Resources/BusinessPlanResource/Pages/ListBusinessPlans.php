<?php

namespace App\Filament\Resources\BusinessPlanResource\Pages;

use App\Filament\Resources\BusinessPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessPlans extends ListRecords
{
    protected static string $resource = BusinessPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createBusinessPlan')),
        ];
    }
}
