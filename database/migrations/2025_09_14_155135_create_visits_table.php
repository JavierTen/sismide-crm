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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entrepreneur_id')->nullable()->index()->comment('Referencia a entrepreneurs.id (emprendedor)');
            $table->date('visit_date')->nullable()->comment('Fecha de la visita (fecha_vista)');
            $table->time('visit_time')->nullable()->comment('Hora de la visita (hora_visita)');
            $table->enum('visit_type', [
                'asistencia_tecnica',
                'caracterizacion',
                'diagnostico',
                'seguimiento'
            ])->default('asistencia_tecnica')->comment('Tipo de visita');
            $table->boolean('strengthened')->default(false)->comment('fortalecido: 1=Si, 0=No');
            $table->boolean('rescheduled')->default(false)->comment('reagendamiento: 1=Si, 0=No');
            $table->text('reschedule_reason')->nullable()->comment('motivo de reagendamiento');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
