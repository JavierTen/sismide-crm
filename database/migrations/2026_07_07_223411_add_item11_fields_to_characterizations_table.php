<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characterizations', function (Blueprint $table) {
            // c. Estado del Emprendimiento
            $table->string('business_current_state')->nullable()->after('characterization_date');
            $table->string('maturity_level')->nullable()->after('business_current_state');

            // d. Características - nuevos campos
            $table->string('market_coverage')->nullable()->after('promotion_strategies');
            $table->string('clients_other')->nullable()->after('market_coverage');
            $table->string('promotion_strategies_other')->nullable()->after('clients_other');
            $table->unsignedInteger('direct_jobs')->nullable()->after('promotion_strategies_other');
            $table->unsignedInteger('indirect_jobs')->nullable()->after('direct_jobs');

            // e. Formalización - expansión condicional
            $table->string('mercantile_registration_number')->nullable()->after('has_commercial_registration');
            $table->date('mercantile_registration_expiry')->nullable()->after('mercantile_registration_number');
            $table->string('accounting_method')->nullable()->after('has_accounting_records');
            $table->string('accounting_method_other')->nullable()->after('accounting_method');
            $table->boolean('has_business_bank_account')->default(false)->after('accounting_method_other');
            $table->string('bank_name')->nullable()->after('has_business_bank_account');
            $table->string('has_operation_licenses')->nullable()->after('bank_name'); // si/no/no_aplica
            $table->text('licenses_description')->nullable()->after('has_operation_licenses');
            $table->string('drummond_family_relationship')->nullable()->after('family_in_drummond');

            // f. Infraestructura
            $table->string('activity_location')->nullable()->after('longitude');
            $table->boolean('is_own_property')->nullable()->after('activity_location');

            // g. Impacto Social
            $table->unsignedInteger('economic_dependents')->nullable()->after('is_own_property');
            $table->unsignedInteger('benefited_families')->nullable()->after('economic_dependents');

            // h. Información Financiera
            $table->decimal('monthly_costs', 15, 2)->nullable()->after('benefited_families');
            $table->decimal('monthly_expenses', 15, 2)->nullable()->after('monthly_costs');
            $table->decimal('monthly_profit', 15, 2)->nullable()->after('monthly_expenses');
            $table->boolean('has_active_credits')->default(false)->after('monthly_profit');
            $table->string('credit_entity')->nullable()->after('has_active_credits');
            $table->decimal('credit_amount', 15, 2)->nullable()->after('credit_entity');
            $table->boolean('has_family_employees')->default(false)->after('credit_amount');
            $table->unsignedInteger('family_employees_count')->nullable()->after('has_family_employees');
            $table->boolean('hires_women')->default(false)->after('family_employees_count');
            $table->unsignedInteger('women_employees_count')->nullable()->after('hires_women');

            // j. Producción y Operación
            $table->string('monthly_production_capacity')->nullable()->after('women_employees_count');
            $table->text('equipment_and_tools')->nullable()->after('monthly_production_capacity');
            $table->text('main_suppliers')->nullable()->after('equipment_and_tools');

            // k. Innovación y Tecnología
            $table->string('tech_capacity_level')->nullable()->after('main_suppliers');
            $table->boolean('has_innovation')->default(false)->after('tech_capacity_level');
            $table->text('innovation_description')->nullable()->after('has_innovation');
            $table->json('digital_tools')->nullable()->after('innovation_description');

            // l. Diagnóstico
            $table->text('main_difficulties')->nullable()->after('digital_tools');
            $table->json('strengthening_needs')->nullable()->after('main_difficulties');

            // m. Habeas Data
            $table->boolean('habeas_data_accepted')->default(false)->after('strengthening_needs');
            $table->timestamp('habeas_data_accepted_at')->nullable()->after('habeas_data_accepted');
            $table->foreignId('habeas_data_manager_id')->nullable()->constrained('users')->nullOnDelete()->after('habeas_data_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('characterizations', function (Blueprint $table) {
            $table->dropForeign(['habeas_data_manager_id']);
            $table->dropColumn([
                'business_current_state', 'maturity_level',
                'market_coverage', 'clients_other', 'promotion_strategies_other',
                'direct_jobs', 'indirect_jobs',
                'mercantile_registration_number', 'mercantile_registration_expiry',
                'accounting_method', 'accounting_method_other',
                'has_business_bank_account', 'bank_name',
                'has_operation_licenses', 'licenses_description',
                'drummond_family_relationship',
                'activity_location', 'is_own_property',
                'economic_dependents', 'benefited_families',
                'monthly_costs', 'monthly_expenses', 'monthly_profit',
                'has_active_credits', 'credit_entity', 'credit_amount',
                'has_family_employees', 'family_employees_count',
                'hires_women', 'women_employees_count',
                'monthly_production_capacity', 'equipment_and_tools', 'main_suppliers',
                'tech_capacity_level', 'has_innovation', 'innovation_description', 'digital_tools',
                'main_difficulties', 'strengthening_needs',
                'habeas_data_accepted', 'habeas_data_accepted_at', 'habeas_data_manager_id',
            ]);
        });
    }
};
