<?php

namespace App\Filament\Resources\EconomicActivityResource\Pages;

use App\Filament\Resources\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEconomicActivity extends EditRecord
{
    protected static string $resource = EconomicActivityResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editEconomicActivity'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listEconomicActivitys')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteEconomicActivity')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
