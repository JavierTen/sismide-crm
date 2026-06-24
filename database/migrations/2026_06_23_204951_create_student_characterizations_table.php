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
        Schema::create('student_characterizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('zone')->nullable();
            $table->string('main_interest')->nullable();
            $table->string('main_interest_other')->nullable();
            $table->longText('life_project')->nullable();
            $table->boolean('has_prior_experience')->default(false);
            $table->string('prior_experience_type')->nullable();
            $table->string('prior_experience_other')->nullable();
            $table->string('participation_status')->default('active');
            $table->date('program_join_date')->nullable();
            $table->date('program_exit_date')->nullable();
            $table->string('exit_reason')->nullable();
            $table->string('exit_reason_other')->nullable();
            $table->boolean('data_authorization')->default(false);
            $table->string('data_authorization_file')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_characterizations');
    }
};
