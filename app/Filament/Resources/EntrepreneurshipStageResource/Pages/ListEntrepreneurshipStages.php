<?php

namespace App\Filament\Resources\EntrepreneurshipStageResource\Pages;

use App\Filament\Resources\EntrepreneurshipStageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntrepreneurshipStages extends ListRecords
{
    protected static string $resource = EntrepreneurshipStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createEntrepreneurshipStageResource')),
        ];
    }
}
