<?php

namespace App\Filament\Resources\BusinessDiagnosisResource\Pages;

use App\Filament\Resources\BusinessDiagnosisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessDiagnoses extends ListRecords
{
    protected static string $resource = BusinessDiagnosisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('createBusinessDiagnosis')),
        ];
    }
}
