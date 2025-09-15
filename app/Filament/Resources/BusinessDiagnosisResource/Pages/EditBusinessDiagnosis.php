<?php

namespace App\Filament\Resources\BusinessDiagnosisResource\Pages;

use App\Filament\Resources\BusinessDiagnosisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessDiagnosis extends EditRecord
{
    protected static string $resource = BusinessDiagnosisResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editBusinessDiagnosis'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listBusinessDiagnosis')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteBusinessDiagnosis')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
