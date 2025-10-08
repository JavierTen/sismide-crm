<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actors', function (Blueprint $table) {
            $table->id();

            // 1. Información General del Actor
            $table->string('name');
            $table->enum('type', [
                'private_company',
                'commercial',
                'guild_association',
                'government_authority',
                'educational_institution',
                'ngo_foundation',
                'financial_entity',
                'other'
            ]);
            $table->string('type_other')->nullable();

            // 2. Contacto Principal
            $table->string('contact_name');
            $table->string('contact_role');
            $table->string('contact_email');
            $table->string('contact_phone');

            // 3. Ubicación y Accesibilidad
            $table->boolean('has_physical_office')->default(false);
            $table->text('office_address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('main_location')->nullable();
            $table->string('office_hours')->nullable();

            // 3.2. Áreas en las que puede aportar (JSON para múltiples selecciones)
            $table->json('contribution_areas')->nullable();
            $table->string('contribution_areas_other')->nullable();

            // 3.3. Experiencias previas
            $table->boolean('has_entrepreneurship_experience')->default(false);
            $table->text('entrepreneurship_experience_details')->nullable();

            // 3.4. Compromisos (JSON para múltiples selecciones)
            $table->json('commitments')->nullable();
            $table->string('commitments_other')->nullable();

            // 5. Utilidad Estratégica del Contacto
            $table->text('market_connection')->nullable();
            $table->text('authority_management')->nullable();
            $table->text('financing_access')->nullable();
            $table->text('training_advisory')->nullable();
            $table->text('logistic_support')->nullable();

            // 7. Valor Diferencial del Actor
            $table->enum('action_scope', [
                'local',
                'regional',
                'national',
                'international'
            ]);

            // Manager que registró
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('type');
            $table->index('has_physical_office');
            $table->index('action_scope');
            $table->index('manager_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actors');
    }
};
