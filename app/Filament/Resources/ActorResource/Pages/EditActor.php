<?php

namespace App\Filament\Resources\ActorResource\Pages;

use App\Filament\Resources\ActorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActor extends EditRecord
{
    protected static string $resource = ActorResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editActor'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listActors')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteCharacterization')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
