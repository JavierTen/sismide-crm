<?php

namespace App\Filament\Resources\EntrepreneurResource\Pages;

use App\Filament\Resources\EntrepreneurResource;
use App\Models\Entrepreneur;
use App\Models\Business;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class CreateEntrepreneur extends CreateRecord
{
    protected static string $resource = EntrepreneurResource::class;

    protected function handleRecordCreation(array $data): Model
{
    return DB::transaction(function () use ($data) {
        // DEBUG: Ver qué datos llegan
        Log::info('Datos completos recibidos:', $data);

        $entrepreneurData = $this->extractEntrepreneurData($data);
        Log::info('Datos del emprendedor:', $entrepreneurData);

        $entrepreneur = Entrepreneur::create($entrepreneurData);

        $businessData = $this->extractBusinessData($data, $entrepreneur->id);
        Log::info('Datos del negocio:', $businessData);

        $business = Business::create($businessData);

        return $entrepreneur->load(['business', 'city', 'documentType', 'gender']);
    });
}

    /**
     * Extraer solo los datos del emprendedor del formulario
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
            'city_id' => $data['city_id'],
            'education_level_id' => $data['education_level_id'],
            'population_id' => $data['population_id'],
            'state_id' => $data['state_id'] ?? null,
            'manager_id' => auth()->id(),
            'project_id' => $data['project_id'] ?? null,
            'service' => $data['service'] ?? null,
            'admission_date' => $data['admission_date'] ?? null,
            'cohort_id' => $data['cohort_id'] ?? null,
            'user_id' => auth()->id(),
            'traffic_light' => $data['traffic_light'] ?? 1,
        ];
    }

    /**
     * Extraer solo los datos del emprendimiento del formulario
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
            'city_id' => $data['city_id'], // city_id del formulario mapea a municipality_id en business
            'ward_id' => $data['ward_id'] ?? null,
            'village_id' => $data['village_id'] ?? null,
            'georeferencing' => $data['georeferencing'] ?? null,
            'ciiu_code_id' => $data['code_ciiu'],
            'entrepreneurship_stage_id' => $data['entrepreneurship_stage_id'],
            'productive_line_id' => $data['productive_line_id'],
            'economic_activity_id' => $data['economic_activity_id'],
            'business_zone' => $data['business_zone'] ?? null,
            'influence_zone' => $data['influence_zone'] ?? null,
            'is_characterized' => $data['is_characterized'] ?? 'No',
            'aid_compliance' => $data['aid_compliance'] ?? 'Does Not Comply',
            'cohort' => $data['cohort'] ?? null,
        ];
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createEntrepreneur'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
