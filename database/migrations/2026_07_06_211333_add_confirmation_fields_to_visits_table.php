<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->string('visit_result')->nullable()->after('reschedule_reason');
            $table->text('topics_and_commitment')->nullable()->after('visit_result');
            $table->json('evidence_path')->nullable()->after('topics_and_commitment');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['visit_result', 'topics_and_commitment', 'evidence_path']);
        });
    }
};
