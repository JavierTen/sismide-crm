<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_training_sessions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('student_training_id');
            $table->unsignedBigInteger('educational_institution_id');

            // ── Datos de la sesión ────────────────────────────────────────────
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('intensity_hours', 5, 2)->nullable(); // autocalculado

            // ── Desarrollo de la sesión ───────────────────────────────────────
            $table->string('methodology');
            $table->string('methodology_other')->nullable();
            $table->text('activity');
            $table->string('result_rating');
            $table->text('result_detail');
            $table->text('observations')->nullable();
            $table->json('commitments')->nullable(); // [{commitment, responsible, due_date}]

            // ── Evidencias ────────────────────────────────────────────────────
            $table->string('attendance_list_path');
            $table->json('additional_material')->nullable();

            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('student_training_id', 'sts_st_fk')
                ->references('id')->on('student_trainings')->cascadeOnDelete();
            $table->foreign('educational_institution_id', 'sts_ei_fk')
                ->references('id')->on('educational_institutions')->cascadeOnDelete();

            // Una capacitación se dicta una sola vez por institución.
            $table->unique(['student_training_id', 'educational_institution_id'], 'sts_unique_training_institution');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_training_sessions');
    }
};
