<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada capacitación puede tener varios facilitadores. Cada uno es una pareja
 * entidad (actors) + capacitador (entity_contacts), ambos de la base de Ruta D.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_training_facilitators', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('student_training_id');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('entity_contact_id');

            $table->timestamps();

            // Nombres cortos explícitos: los generados automáticamente superan
            // el límite de 64 caracteres de MySQL.
            $table->foreign('student_training_id', 'stf_st_fk')
                ->references('id')->on('student_trainings')->cascadeOnDelete();
            $table->foreign('actor_id', 'stf_a_fk')
                ->references('id')->on('actors')->cascadeOnDelete();
            $table->foreign('entity_contact_id', 'stf_ec_fk')
                ->references('id')->on('entity_contacts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_training_facilitators');
    }
};
