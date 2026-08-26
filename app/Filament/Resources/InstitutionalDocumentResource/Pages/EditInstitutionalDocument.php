<?php

namespace App\Filament\Resources\InstitutionalDocumentResource\Pages;

use App\Filament\Resources\InstitutionalDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstitutionalDocument extends EditRecord
{
    protected static string $resource = InstitutionalDocumentResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editInstitutionalDocument'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
