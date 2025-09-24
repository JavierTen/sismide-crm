<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users permissions
            'createUser',
            'editUser',
            'listUsers',
            'deleteUser',

            // Document Types permissions
            'createTypeDocument',
            'editTypeDocument',
            'listTypeDocuments',
            'deleteTypeDocument',

            // Genders permissions
            'createGender',
            'editGender',
            'listGenders',
            'deleteGender',

            // Marital Status permissions
            'createMaritalStatus',
            'editMaritalStatus',
            'listMaritalStatuses',
            'deleteMaritalStatus',

            // Education Level permissions
            'createEducationLevel',
            'editEducationLevel',
            'listEducationLevels',
            'deleteEducationLevel',

            // Population permissions
            'createPopulation',
            'editPopulation',
            'listPopulations',
            'deletePopulation',

            // Entrepreneur permissions
            'createEntrepreneur',
            'editEntrepreneur',
            'listEntrepreneurs',
            'deleteEntrepreneur',

            // Entrepreneurship Stage permissions
            'createEntrepreneurshipStageResource',
            'editEntrepreneurshipStageResource',
            'listEntrepreneurshipStageResources',
            'deleteEntrepreneurshipStageResource',

            // Economic Activity permissions
            'createEconomicActivity',
            'editEconomicActivity',
            'listEconomicActivitys',
            'deleteEconomicActivity',

            // Productive Line permissions
            'createProductiveLine',
            'editProductiveLine',
            'listProductiveLines',
            'deleteProductiveLine',

            // CIIU Code permissions
            'createCiiuCode',
            'editCiiuCode',
            'listCiiuCodes',
            'deleteCiiuCode',

            // Project permissions
            'createProject',
            'editProject',
            'listProjects',
            'deleteProject',

            // Department permissions
            'createDepartment',
            'editDepartment',
            'listDepartments',
            'deleteDepartment',

            // City permissions
            'createCity',
            'editCity',
            'listCities',
            'deleteCity',

            // Ward permissions
            'createWard',
            'editWard',
            'listWards',
            'deleteWard',

            // Visit permissions
            'createVisit',
            'editVisit',
            'listVisits',
            'deleteVisit',

            // Characterization permissions
            'createCharacterization',
            'editCharacterization',
            'listCharacterizations',
            'deleteCharacterization',

            // Business Diagnosis permissions
            'createBusinessDiagnosis',
            'editBusinessDiagnosis',
            'listBusinessDiagnosis',
            'deleteBusinessDiagnosis',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
