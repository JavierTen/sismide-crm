<?php

namespace App\Filament\Resources\EducationLevelResource\Pages;

use App\Filament\Resources\EducationLevelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationLevel extends CreateRecord
{
    protected static string $resource = EducationLevelResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createEducationLevel'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
