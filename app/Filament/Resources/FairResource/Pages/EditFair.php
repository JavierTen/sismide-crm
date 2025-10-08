<?php

namespace App\Filament\Resources\FairResource\Pages;

use App\Filament\Resources\FairResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFair extends EditRecord
{
    protected static string $resource = FairResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editFair'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listFairs')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteFair')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
