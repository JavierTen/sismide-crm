<?php

namespace App\Filament\Resources\EntrepreneurshipStageResource\Pages;

use App\Filament\Resources\EntrepreneurshipStageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEntrepreneurshipStage extends CreateRecord
{
    protected static string $resource = EntrepreneurshipStageResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createEntrepreneurshipStageResource'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
