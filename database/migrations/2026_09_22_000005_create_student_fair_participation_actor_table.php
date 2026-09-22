<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fair_participation_actor', function (Blueprint $table) {
            $table->unsignedBigInteger('student_fair_participation_id');
            $table->unsignedBigInteger('actor_id');

            $table->primary(['student_fair_participation_id', 'actor_id'], 'sfpa_pk');

            $table->foreign('student_fair_participation_id', 'sfpa_sfp_fk')
                ->references('id')->on('student_fair_participations')->cascadeOnDelete();
            $table->foreign('actor_id', 'sfpa_a_fk')
                ->references('id')->on('actors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fair_participation_actor');
    }
};
