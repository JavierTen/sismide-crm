<?php

namespace App\Filament\Resources\BusinessPlanResource\Pages;

use App\Filament\Resources\BusinessPlanResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessPlan extends CreateRecord
{
    protected static string $resource = BusinessPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['logo_path'])) {
            Notification::make()
                ->danger()
                ->title('El logo del emprendimiento es obligatorio.')
                ->send();
            $this->halt();
        }

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
