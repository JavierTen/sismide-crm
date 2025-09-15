<?php

namespace App\Filament\Resources\CharacterizationResource\Pages;

use App\Filament\Resources\CharacterizationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCharacterization extends CreateRecord
{
    protected static string $resource = CharacterizationResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createCharacterization'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
