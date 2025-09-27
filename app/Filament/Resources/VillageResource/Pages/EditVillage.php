<?php

namespace App\Filament\Resources\VillageResource\Pages;

use App\Filament\Resources\VillageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVillage extends EditRecord
{
    protected static string $resource = VillageResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editVillage'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listVillages')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteVillage')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
