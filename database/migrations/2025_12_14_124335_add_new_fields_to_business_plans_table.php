<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_plans', function (Blueprint $table) {
            $table->string('acquisition_matrix_path')->nullable()->after('business_plan_path');
            $table->string('fire_pitch_video_url')->nullable()->after('acquisition_matrix_path');
            $table->string('production_cycle_video_url')->nullable()->after('fire_pitch_video_url');
            $table->string('business_model_path')->nullable()->after('production_cycle_video_url');
            $table->string('logo_path')->nullable()->after('business_model_path');
            $table->boolean('is_prioritized')->default(false)->after('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_plans', function (Blueprint $table) {
            $table->dropColumn([
                'acquisition_matrix_path',
                'fire_pitch_video_url',
                'production_cycle_video_url',
                'business_model_path',
                'logo_path',
                'is_prioritized',
            ]);
        });
    }
};
