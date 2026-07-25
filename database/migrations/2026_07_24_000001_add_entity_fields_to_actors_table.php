<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            // Identificación de la entidad
            $table->string('nit')->nullable()->after('type_other');
            $table->enum('nature', [
                'public', 'private', 'mixed', 'academia',
                'social_organization', 'cooperation', 'guild_association',
            ])->nullable()->after('nit');
            $table->string('economic_sector')->nullable()->after('nature');
            $table->string('institutional_phone')->nullable()->after('economic_sector');
            $table->string('institutional_email')->nullable()->after('institutional_phone');
            $table->string('website')->nullable()->after('institutional_email');
            $table->text('description')->nullable()->after('website');
            $table->enum('linkage_status', [
                'identified', 'pending_contact', 'contacted',
                'in_management', 'linked', 'active_commitment', 'inactive',
            ])->nullable()->after('description');
            // Se usa SQL directo porque Doctrine DBAL no soporta introspección de columnas enum
        });

        DB::statement("ALTER TABLE actors MODIFY action_scope ENUM('local', 'regional', 'national', 'international') NULL DEFAULT NULL");

        Schema::table('actors', function (Blueprint $table) {

            // Cobertura territorial
            $table->json('territorial_coverage')->nullable()->after('office_hours');
            $table->json('attention_modality')->nullable()->after('territorial_coverage');

            // Experiencia previa (expandida)
            $table->json('experience_population_type')->nullable()->after('entrepreneurship_experience_details');
            $table->json('experience_economic_sectors')->nullable()->after('experience_population_type');
            $table->json('experience_territories')->nullable()->after('experience_economic_sectors');
            $table->unsignedInteger('experience_entrepreneurships_count')->nullable()->after('experience_territories');
            $table->string('experience_evidence_path')->nullable()->after('experience_entrepreneurships_count');

            // Compromisos con Ruta D (reestructurado)
            $table->enum('commitments_confirmed', ['yes', 'no', 'pending'])->nullable()->after('commitments_other');
            $table->json('commitment_types')->nullable()->after('commitments_confirmed');
            $table->text('commitment_description')->nullable()->after('commitment_types');
            $table->string('commitment_responsible_contact')->nullable()->after('commitment_description');
            $table->json('commitment_routes')->nullable()->after('commitment_responsible_contact');
            $table->json('commitment_municipalities')->nullable()->after('commitment_routes');
            $table->string('commitment_modality')->nullable()->after('commitment_municipalities');
            $table->unsignedInteger('commitment_estimated_count')->nullable()->after('commitment_modality');
            $table->text('commitment_observations')->nullable()->after('commitment_estimated_count');

            // Utilidad estratégica — toggles y tipos
            $table->boolean('market_connection_enabled')->default(false)->after('logistic_support');
            $table->json('market_connection_types')->nullable()->after('market_connection_enabled');
            $table->boolean('authority_management_enabled')->default(false)->after('market_connection_types');
            $table->boolean('financing_access_enabled')->default(false)->after('authority_management_enabled');
            $table->json('financing_types')->nullable()->after('financing_access_enabled');
            $table->boolean('training_advisory_enabled')->default(false)->after('financing_types');
            $table->json('training_service_types')->nullable()->after('training_advisory_enabled');
            $table->boolean('logistic_support_enabled')->default(false)->after('training_service_types');
            $table->json('logistic_support_types')->nullable()->after('logistic_support_enabled');
            $table->boolean('organizational_strengthening_enabled')->default(false)->after('logistic_support_types');
            $table->text('organizational_strengthening_desc')->nullable()->after('organizational_strengthening_enabled');
            $table->boolean('innovation_digital_enabled')->default(false)->after('organizational_strengthening_desc');
            $table->text('innovation_digital_desc')->nullable()->after('innovation_digital_enabled');

            // Articulación por rutas
            $table->boolean('route_1_enabled')->default(false)->after('innovation_digital_desc');
            $table->json('route_1_support_types')->nullable()->after('route_1_enabled');
            $table->boolean('route_2_enabled')->default(false)->after('route_1_support_types');
            $table->json('route_2_support_types')->nullable()->after('route_2_enabled');
            $table->boolean('route_3_enabled')->default(false)->after('route_2_support_types');
            $table->json('route_3_support_types')->nullable()->after('route_3_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn([
                'nit', 'nature', 'economic_sector', 'institutional_phone',
                'institutional_email', 'website', 'description', 'linkage_status',
                'territorial_coverage', 'attention_modality',
                'experience_population_type', 'experience_economic_sectors',
                'experience_territories', 'experience_entrepreneurships_count',
                'experience_evidence_path',
                'commitments_confirmed', 'commitment_types', 'commitment_description',
                'commitment_responsible_contact', 'commitment_routes',
                'commitment_municipalities', 'commitment_modality',
                'commitment_estimated_count', 'commitment_observations',
                'market_connection_enabled', 'market_connection_types',
                'authority_management_enabled',
                'financing_access_enabled', 'financing_types',
                'training_advisory_enabled', 'training_service_types',
                'logistic_support_enabled', 'logistic_support_types',
                'organizational_strengthening_enabled', 'organizational_strengthening_desc',
                'innovation_digital_enabled', 'innovation_digital_desc',
                'route_1_enabled', 'route_1_support_types',
                'route_2_enabled', 'route_2_support_types',
                'route_3_enabled', 'route_3_support_types',
            ]);
        });
    }
};
