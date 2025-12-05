<?php

namespace App\Filament\Resources\BusinessPlanResource\Pages;

use App\Filament\Resources\BusinessPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessPlan extends CreateRecord
{
    protected static string $resource = BusinessPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createBusinessPlan'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
