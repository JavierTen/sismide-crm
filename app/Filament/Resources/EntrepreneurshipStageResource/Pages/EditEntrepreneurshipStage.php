<?php

namespace App\Filament\Resources\EntrepreneurshipStageResource\Pages;

use App\Filament\Resources\EntrepreneurshipStageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEntrepreneurshipStage extends EditRecord
{
    protected static string $resource = EntrepreneurshipStageResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editEntrepreneurshipStageResource'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listEntrepreneurshipStageResources')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteEntrepreneurshipStageResource')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
