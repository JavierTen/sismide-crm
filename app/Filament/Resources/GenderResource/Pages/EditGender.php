<?php

namespace App\Filament\Resources\GenderResource\Pages;

use App\Filament\Resources\GenderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGender extends EditRecord
{
    protected static string $resource = GenderResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editGender'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listGenders')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteGender')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
