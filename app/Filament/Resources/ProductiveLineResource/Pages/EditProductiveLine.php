<?php

namespace App\Filament\Resources\ProductiveLineResource\Pages;

use App\Filament\Resources\ProductiveLineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductiveLine extends EditRecord
{
    protected static string $resource = ProductiveLineResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editProductiveLine'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listProductiveLines')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteProductiveLine')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
