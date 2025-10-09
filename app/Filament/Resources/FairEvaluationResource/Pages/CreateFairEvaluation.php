<?php

namespace App\Filament\Resources\FairEvaluationResource\Pages;

use App\Filament\Resources\FairEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFairEvaluation extends CreateRecord
{
    protected static string $resource = FairEvaluationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createFairParticipation'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


}
