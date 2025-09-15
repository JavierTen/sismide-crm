<?php

namespace App\Filament\Resources\EntrepreneurResource\Pages;

use App\Filament\Resources\EntrepreneurResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use App\Models\Business;
use Illuminate\Database\Eloquent\Model;


class EditEntrepreneur extends EditRecord
{
    protected static string $resource = EntrepreneurResource::class;

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editEntrepreneur'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listEntrepreneurs')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteEntrepreneur')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


    protected function mutateFormDataBeforeFill(array $data): array
    {
        $entrepreneur = $this->record;

        // Si el emprendedor tiene negocio, combinar los datos
        if ($entrepreneur->business) {
            $business = $entrepreneur->business;

            $data = array_merge($data, [
                // Datos básicos del emprendimiento
                'business_name' => $business->business_name,
                'business_description' => $business->description,
                'creation_date' => $business->creation_date,

                // IDs de relaciones del emprendimiento
                'entrepreneurship_stage_id' => $business->entrepreneurship_stage_id,
                'economic_activity_id' => $business->economic_activity_id,
                'productive_line_id' => $business->productive_line_id,
                'code_ciiu' => $business->ciiu_code_id,
                'marsital_statusS' => $business->project_id, // Nota: corregir este nombre en el formulario

                // Contacto del emprendimiento
                'business_phone' => $business->phone,
                'business_email' => $business->email,
                'business_address' => $business->address,

                // Ubicación del emprendimiento
                'department_id' => $business->department_id,
                'city_id' => $business->city_id,
                'ward_id' => $business->ward_id,
                'georeferencing' => $business->georeferencing,

                // Características del emprendimiento
                'business_zone' => $business->business_zone,
                'influence_zone' => $business->influence_zone,
                'is_characterized' => $business->is_characterized,
                'aid_compliance' => $business->aid_compliance,
            ]);
        }

        return $data;
    }

    /**
     * Handle record update with transaction for both entrepreneur and business
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // 1. Actualizar emprendedor
            $entrepreneurData = $this->extractEntrepreneurData($data);
            $record->update($entrepreneurData);

            // 2. Actualizar o crear emprendimiento
            $businessData = $this->extractBusinessData($data, $record->id);

            if ($record->business) {
                // Actualizar emprendimiento existente
                $record->business->update($businessData);
            } else {
                // Crear nuevo emprendimiento si no existe
                Business::create($businessData);
            }

            // 3. Refrescar modelo con relaciones
            return $record->fresh(['business', 'city', 'documentType', 'gender']);
        });
    }

    /**
     * Extraer datos del emprendedor para actualización
     */
    private function extractEntrepreneurData(array $data): array
    {
        return [
            'status' => $data['status'] ?? true,
            'document_type_id' => $data['document_type_id'],
            'document_number' => $data['document_number'],
            'full_name' => $data['full_name'],
            'gender_id' => $data['gender_id'],
            'marital_status_id' => $data['marital_status_id'] ?? null,
            'birth_date' => $data['birth_date'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'email' => $data['email'],
            'city_id' => $data['entrepreneur_city_id'] ?? $data['city_id'], // Solo si tienes ubicación separada del emprendedor
            'education_level_id' => $data['education_level_id'],
            'population_id' => $data['population_id'],
            'state_id' => $data['state_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'project_id' => $data['marsital_statusS'] ?? null, // Mapear correctamente
            'service' => $data['service'] ?? null,
            'admission_date' => $data['admission_date'] ?? null,
            'cohort_id' => $data['cohort_id'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'traffic_light' => $data['traffic_light'] ?? 1,
        ];
    }

    /**
     * Extraer datos del emprendimiento para actualización
     */
    private function extractBusinessData(array $data, int $entrepreneurId): array
    {
        return [
            'entrepreneur_id' => $entrepreneurId,
            'business_name' => $data['business_name'],
            'description' => $data['business_description'] ?? null,
            'creation_date' => $data['creation_date'] ?? null,
            'status' => 'Active',
            'phone' => $data['business_phone'],
            'email' => $data['business_email'],
            'address' => $data['business_address'],
            'department_id' => $data['department_id'],
            'city_id' => $data['city_id'],
            'ward_id' => $data['ward_id'] ?? null,
            'georeferencing' => $data['georeferencing'] ?? null,
            'ciiu_code_id' => $data['code_ciiu'],
            'entrepreneurship_stage_id' => $data['entrepreneurship_stage_id'],
            'productive_line_id' => $data['productive_line_id'],
            'economic_activity_id' => $data['economic_activity_id'],
            'business_zone' => $data['business_zone'] ?? null,
            'influence_zone' => $data['influence_zone'] ?? null,
            'is_characterized' => $data['is_characterized'] ?? 'No',
            'aid_compliance' => $data['aid_compliance'] ?? 'Does Not Comply',
        ];
    }

    /**
     * Custom success message
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Emprendedor y emprendimiento actualizados exitosamente';
    }





}
