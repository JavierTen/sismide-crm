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
        Schema::create('entrepreneurs', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->default(true);
            $table->foreignId('document_type_id')->nullable()->comment('Document type of the entrepreneur (reference to "document_types" table)');
            $table->string('document_number', 20)->nullable()->comment('Entrepreneur document number');
            $table->string('full_name', 100)->nullable()->comment('Full name of the entrepreneur');
            $table->foreignId('gender_id')->nullable();
            $table->foreignId('marital_status_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('email', 100)->nullable();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('education_level_id')->nullable();
            $table->foreignId('population_id')->nullable();
            $table->foreignId('state_id')->nullable();
            $table->foreignId('manager_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->string('service', 50)->nullable();
            $table->date('admission_date')->nullable();
            $table->foreignId('cohort_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->integer('traffic_light')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrepreneurs');
    }
};
