<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asistencia. Se guarda una fila por cada estudiante registrado de la
 * institución, marcado o no: `attended` distingue a quien asistió de quien
 * quedó convocado sin asistir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_training_session_student', function (Blueprint $table) {
            $table->unsignedBigInteger('student_training_session_id');
            $table->unsignedBigInteger('student_id');
            $table->boolean('attended')->default(false);

            $table->primary(['student_training_session_id', 'student_id'], 'stss_pk');

            $table->foreign('student_training_session_id', 'stss_sts_fk')
                ->references('id')->on('student_training_sessions')->cascadeOnDelete();
            $table->foreign('student_id', 'stss_s_fk')
                ->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_training_session_student');
    }
};
