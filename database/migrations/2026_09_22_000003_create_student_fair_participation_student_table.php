<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fair_participation_student', function (Blueprint $table) {
            $table->unsignedBigInteger('student_fair_participation_id');
            $table->unsignedBigInteger('student_id');

            $table->primary(['student_fair_participation_id', 'student_id'], 'sfps_pk');

            $table->foreign('student_fair_participation_id', 'sfps_sfp_fk')
                ->references('id')->on('student_fair_participations')->cascadeOnDelete();
            $table->foreign('student_id', 'sfps_s_fk')
                ->references('id')->on('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fair_participation_student');
    }
};
