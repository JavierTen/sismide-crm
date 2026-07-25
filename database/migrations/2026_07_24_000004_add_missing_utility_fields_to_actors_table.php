<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            // Gestiones con autoridades — campos adicionales
            $table->text('authority_management_entities')->nullable()->after('authority_management');
            $table->json('authority_management_routes')->nullable()->after('authority_management_entities');

            // Capacitación, mentoría o asesoría — campos adicionales
            $table->string('training_modality')->nullable()->after('training_service_types');
            $table->unsignedInteger('training_capacity_count')->nullable()->after('training_modality');

            // Apoyo logístico — campo adicional
            $table->text('logistic_support_coverage')->nullable()->after('logistic_support');
        });
    }

    public function down(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            $table->dropColumn([
                'authority_management_entities',
                'authority_management_routes',
                'training_modality',
                'training_capacity_count',
                'logistic_support_coverage',
            ]);
        });
    }
};
