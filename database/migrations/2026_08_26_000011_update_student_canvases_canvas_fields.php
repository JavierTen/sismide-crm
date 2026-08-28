<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_canvases', function (Blueprint $table) {
            $table->dropColumn([
                'customer_segments',
                'value_proposition',
                'channels',
                'customer_relationships',
                'revenue_streams',
                'key_resources',
                'key_activities',
                'key_partnerships',
                'cost_structure',
            ]);
        });

        Schema::table('student_canvases', function (Blueprint $table) {
            $table->text('problem_identification')->nullable()->after('manager_id');
            $table->text('business_idea')->nullable()->after('problem_identification');
            $table->text('differentiator')->nullable()->after('business_idea');
            $table->text('achievements')->nullable()->after('differentiator');
            $table->text('business_model_description')->nullable()->after('achievements');
            $table->text('next_steps')->nullable()->after('business_model_description');
        });
    }

    public function down(): void
    {
        Schema::table('student_canvases', function (Blueprint $table) {
            $table->dropColumn([
                'problem_identification',
                'business_idea',
                'differentiator',
                'achievements',
                'business_model_description',
                'next_steps',
            ]);
        });

        Schema::table('student_canvases', function (Blueprint $table) {
            $table->text('customer_segments')->nullable()->after('manager_id');
            $table->text('value_proposition')->nullable()->after('customer_segments');
            $table->text('channels')->nullable()->after('value_proposition');
            $table->text('customer_relationships')->nullable()->after('channels');
            $table->text('revenue_streams')->nullable()->after('customer_relationships');
            $table->text('key_resources')->nullable()->after('revenue_streams');
            $table->text('key_activities')->nullable()->after('key_resources');
            $table->text('key_partnerships')->nullable()->after('key_activities');
            $table->text('cost_structure')->nullable()->after('key_partnerships');
        });
    }
};
