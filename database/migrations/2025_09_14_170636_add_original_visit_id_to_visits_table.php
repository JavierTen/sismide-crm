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
        Schema::table('visits', function (Blueprint $table) {
            $table->unsignedBigInteger('original_visit_id')->nullable()->after('reschedule_reason')->index()->comment('If this is a reschedule, reference the original visit');
            // Optional FK:
            // $table->foreign('original_visit_id')->references('id')->on('visits')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            // If you uncommented the FK, drop it first.
            // $table->dropForeign(['original_visit_id']);
            $table->dropColumn('original_visit_id');
        });
    }
};
