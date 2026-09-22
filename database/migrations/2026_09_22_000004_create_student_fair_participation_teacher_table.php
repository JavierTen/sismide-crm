<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fair_participation_teacher', function (Blueprint $table) {
            $table->unsignedBigInteger('student_fair_participation_id');
            $table->unsignedBigInteger('teacher_id');

            $table->primary(['student_fair_participation_id', 'teacher_id'], 'sfpt_pk');

            $table->foreign('student_fair_participation_id', 'sfpt_sfp_fk')
                ->references('id')->on('student_fair_participations')->cascadeOnDelete();
            $table->foreign('teacher_id', 'sfpt_t_fk')
                ->references('id')->on('teachers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fair_participation_teacher');
    }
};
