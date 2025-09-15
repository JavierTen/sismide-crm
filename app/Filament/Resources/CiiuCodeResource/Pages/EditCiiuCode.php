<?php

namespace App\Filament\Resources\CiiuCodeResource\Pages;

use App\Filament\Resources\CiiuCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCiiuCode extends EditRecord
{
    protected static string $resource = CiiuCodeResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editCiiuCode'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listCiiuCodes')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteCiiuCode')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
