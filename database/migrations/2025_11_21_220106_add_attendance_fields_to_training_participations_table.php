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
        Schema::table('training_participations', function (Blueprint $table) {
            $table->boolean('attended')->after('entrepreneur_id');

            $table->text('non_attendance_reason')->nullable()->after('attended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_participations', function (Blueprint $table) {
            $table->dropColumn(['attended', 'non_attendance_reason']);
        });
    }
};
