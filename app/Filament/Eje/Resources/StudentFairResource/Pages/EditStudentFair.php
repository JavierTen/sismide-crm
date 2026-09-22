<?php

namespace App\Filament\Eje\Resources\StudentFairResource\Pages;

use App\Filament\Eje\Resources\StudentFairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentFair extends EditRecord
{
    protected static string $resource = StudentFairResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editStudentFair'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Deshabilitar')
                ->visible(fn () => ! $this->record->trashed() && auth()->user()->can('deleteStudentFair')),
            Actions\RestoreAction::make()
                ->visible(fn () => $this->record->trashed()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
