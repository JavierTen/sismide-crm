<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'attendance_pending', 'support_pending', 'complete'])
                ->default('scheduled')
                ->after('route');

            $table->string('location', 500)->nullable()->after('modality');

            $table->foreignId('actor_id')
                ->nullable()
                ->after('organizer_email')
                ->constrained('actors')
                ->nullOnDelete();

            $table->foreignId('entity_contact_id')
                ->nullable()
                ->after('actor_id')
                ->constrained('entity_contacts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->dropForeign(['entity_contact_id']);
            $table->dropColumn(['status', 'location', 'actor_id', 'entity_contact_id']);
        });
    }
};
