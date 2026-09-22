<?php

namespace App\Filament\Eje\Resources\StudentFairParticipationResource\Pages;

use App\Filament\Eje\Resources\StudentFairParticipationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentFairParticipation extends EditRecord
{
    protected static string $resource = StudentFairParticipationResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editStudentFairParticipation'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Deshabilitar')
                ->visible(fn () => ! $this->record->trashed() && auth()->user()->can('deleteStudentFairParticipation')),
            Actions\RestoreAction::make()
                ->visible(fn () => $this->record->trashed()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
