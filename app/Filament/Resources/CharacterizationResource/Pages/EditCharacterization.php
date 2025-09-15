<?php

namespace App\Filament\Resources\CharacterizationResource\Pages;

use App\Filament\Resources\CharacterizationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCharacterization extends EditRecord
{
    protected static string $resource = CharacterizationResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editCharacterization'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listCharacterizations')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteCharacterization')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
