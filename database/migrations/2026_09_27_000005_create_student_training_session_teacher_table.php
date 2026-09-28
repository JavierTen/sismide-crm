<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_training_session_teacher', function (Blueprint $table) {
            $table->unsignedBigInteger('student_training_session_id');
            $table->unsignedBigInteger('teacher_id');

            $table->primary(['student_training_session_id', 'teacher_id'], 'stst_pk');

            $table->foreign('student_training_session_id', 'stst_sts_fk')
                ->references('id')->on('student_training_sessions')->cascadeOnDelete();
            $table->foreign('teacher_id', 'stst_t_fk')
                ->references('id')->on('teachers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_training_session_teacher');
    }
};
