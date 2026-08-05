<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'entrepreneurs',
        'businesses',
        'visits',
        'characterizations',
        'business_diagnoses',
        'business_plans',
        'business_plan_evaluations',
        'trainings',
        'training_participations',
        'training_supports',
        'fairs',
        'fair_evaluations',
        'pqrfs',
        'actors',
        'entity_contacts',
        'students',
        'student_characterizations',
        'teachers',
        'educational_institutions',
        'projects',
        'ciiu_codes',
        'cities',
        'departments',
        'document_types',
        'economic_activities',
        'education_levels',
        'entrepreneurship_stages',
        'genders',
        'marital_statuses',
        'populations',
        'productive_lines',
        'villages',
        'wards',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('updated_by_id')
                    ->nullable()
                    ->after('updated_at')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('updated_by_id');
            });
        }
    }
};
