<?php

namespace App\Filament\Resources\TrainingParticipationResource\Pages;

use App\Filament\Resources\TrainingParticipationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingParticipation extends EditRecord
{
    protected static string $resource = TrainingParticipationResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editTrainingParticipation'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listTrainingParticipations')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteTrainingParticipation')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
