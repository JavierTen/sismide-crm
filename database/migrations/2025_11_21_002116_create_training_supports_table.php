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
        Schema::create('training_supports', function (Blueprint $table) {
            $table->id();

            // Relación con la capacitación
            $table->foreignId('training_id')->constrained('trainings')->onDelete('cascade');

            // Campos comunes (para ambas modalidades)
            $table->string('attendance_list_path'); // Lista de asistencia (obligatorio)

            // Campos para modalidad VIRTUAL
            $table->string('recording_link')->nullable(); // Link de grabación (obligatorio si es virtual)

            // Campos para modalidad PRESENCIAL
            $table->string('georeference_photo_path')->nullable(); // Foto con georreferenciación (obligatorio si es presencial)
            $table->string('additional_photo_1_path')->nullable(); // Foto adicional 1 (opcional)
            $table->string('additional_photo_2_path')->nullable(); // Foto adicional 2 (opcional)
            $table->string('additional_photo_3_path')->nullable(); // Foto adicional 3 (opcional)

            // Observaciones
            $table->text('observations')->nullable();

            // Gestor que registra el soporte
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Constraint: solo un soporte por capacitación
            $table->unique('training_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_supports');
    }
};
