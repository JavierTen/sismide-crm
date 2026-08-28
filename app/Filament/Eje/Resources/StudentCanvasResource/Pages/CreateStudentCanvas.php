<?php

namespace App\Filament\Eje\Resources\StudentCanvasResource\Pages;

use App\Filament\Eje\Resources\StudentCanvasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentCanvas extends CreateRecord
{
    protected static string $resource = StudentCanvasResource::class;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createStudentCanvas'), 403);
        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
