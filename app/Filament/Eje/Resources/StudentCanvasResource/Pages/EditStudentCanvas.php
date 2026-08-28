<?php

namespace App\Filament\Eje\Resources\StudentCanvasResource\Pages;

use App\Filament\Eje\Resources\StudentCanvasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentCanvas extends EditRecord
{
    protected static string $resource = StudentCanvasResource::class;

    public function mount(int|string $record): void
    {
        abort_unless(auth()->user()->can('editStudentCanvas'), 403);
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
