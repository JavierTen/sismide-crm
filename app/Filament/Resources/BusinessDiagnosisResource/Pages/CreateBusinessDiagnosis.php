<?php

namespace App\Filament\Resources\BusinessDiagnosisResource\Pages;

use App\Filament\Resources\BusinessDiagnosisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateBusinessDiagnosis extends CreateRecord
{
    protected static string $resource = BusinessDiagnosisResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        return $data;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createBusinessDiagnosis'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ✅ VALIDAR ANTES DE CREAR
    protected function beforeCreate(): void
    {
        $entrepreneurId = $this->data['entrepreneur_id'] ?? null;
        $diagnosisType = $this->data['diagnosis_type'] ?? null;

        if ($entrepreneurId && $diagnosisType) {
            $exists = \App\Models\BusinessDiagnosis::where('entrepreneur_id', $entrepreneurId)
                ->where('diagnosis_type', $diagnosisType)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                $tipo = $diagnosisType === 'entry' ? 'entrada' : 'salida';
                
                Notification::make()
                    ->danger()
                    ->title('⚠️ No se puede crear el diagnóstico')
                    ->body("Este emprendedor ya tiene un diagnóstico de **{$tipo}**.\n\nNo se puede crear otro diagnóstico del mismo tipo.")
                    ->persistent()
                    ->send();
                
                // Detener la creación
                $this->halt();
            }
        }
    }
}