<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('entity_id')->constrained('actors')->cascadeOnDelete();

            $table->string('name');
            $table->string('role')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('area')->nullable();

            $table->enum('contact_type', [
                'directive', 'institutional', 'commercial', 'financial',
                'technical', 'formative', 'logistic', 'other',
            ])->nullable();

            $table->enum('decision_level', ['decision_maker', 'influencer', 'operational'])->nullable();

            $table->boolean('is_primary_contact')->default(false);

            $table->enum('status', ['active', 'pending_contact', 'no_response', 'withdrawn', 'inactive'])->default('active');

            $table->text('notes')->nullable();

            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('entity_id');
            $table->index('is_primary_contact');
            $table->index('status');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_contacts');
    }
};
