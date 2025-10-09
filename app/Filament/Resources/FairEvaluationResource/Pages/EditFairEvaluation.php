<?php

namespace App\Filament\Resources\FairEvaluationResource\Pages;

use App\Filament\Resources\FairEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFairEvaluation extends EditRecord
{
    protected static string $resource = FairEvaluationResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editFairParticipation'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listFairParticipations')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteFairParticipation')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
