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
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();

            // Datos básicos de la capacitación
            $table->string('name');
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->dateTime('training_date');
            $table->enum('route', ['route_1', 'route_2', 'route_3'])->comment('route_1: Pre-emprendimiento, route_2: Consolidación, route_3: Escalamiento');

            // Datos del organizador
            $table->string('organizer_name');
            $table->string('organizer_position');
            $table->string('organizer_phone');
            $table->string('organizer_entity');
            $table->string('organizer_email');

            // Modalidad y archivos
            $table->enum('modality', ['virtual', 'in_person']);
            $table->string('ppt_file_path')->nullable();
            $table->string('promotional_file_path')->nullable();
            $table->string('recording_link')->nullable();

            // Descripción
            $table->text('objective')->nullable();

            // Gestor que registra
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
