<?php

namespace App\Filament\Resources\BusinessPlanResource\Pages;

use App\Filament\Resources\BusinessPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessPlan extends EditRecord
{
    protected static string $resource = BusinessPlanResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editBusinessPlan'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listBusinessPlans')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteBusinessPlan')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
