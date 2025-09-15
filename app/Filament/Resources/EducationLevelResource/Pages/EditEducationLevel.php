<?php

namespace App\Filament\Resources\EducationLevelResource\Pages;

use App\Filament\Resources\EducationLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationLevel extends EditRecord
{
    protected static string $resource = EducationLevelResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editEducationLevel'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listEducationLevels')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteEducationLevel')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
