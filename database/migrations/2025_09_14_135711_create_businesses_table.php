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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();

            // Foreign key to entrepreneur (cascade delete)
            $table->foreignId('entrepreneur_id')
                ->constrained('entrepreneurs')
                ->onDelete('cascade')
                ->comment('Reference to entrepreneur who owns this business');

            // Business basic information
            $table->string('business_name', 100)->nullable()->comment('Business commercial name');
            $table->text('description')->nullable()->comment('Business description');
            $table->date('creation_date')->nullable()->comment('Business creation date');
            $table->enum('status', ['Active', 'Inactive'])->default('Active')->comment('Business status');

            // Contact information
            $table->string('phone', 50)->nullable()->comment('Business phone number');
            $table->string('email', 100)->nullable()->comment('Business email');
            $table->string('address', 200)->nullable()->comment('Business address');

            // Geographic location
            $table->foreignId('department_id')->nullable()->constrained('departments')->comment('Department where business is located');
            $table->foreignId('city_id')->nullable()->constrained('cities')->comment('Municipality where business is located');
            $table->foreignId('ward_id')->nullable()->constrained('wards')->comment('Ward where business is located');
            $table->string('georeferencing', 100)->nullable()->comment('GPS coordinates or geographic reference');

            // Business classification
            $table->foreignId('ciiu_code_id')->nullable()->constrained('ciiu_codes')->comment('CIIU economic activity code');
            $table->foreignId('entrepreneurship_stage_id')->nullable()->constrained('entrepreneurship_stages')->comment('Current stage of the business');
            $table->foreignId('productive_line_id')->nullable()->constrained('productive_lines')->comment('Productive line or sector');

            // Business characteristics
            $table->enum('business_zone', ['Rural', 'Urban'])->nullable()->comment('Business location type');
            $table->enum('influence_zone', ['Complies', 'Does Not Comply'])->nullable()->comment('Influence zone compliance');
            $table->enum('is_characterized', ['Yes', 'No'])->nullable()->comment('Whether business has been characterized');
            $table->enum('aid_compliance', ['Complies', 'Does Not Comply'])->default('Does Not Comply')->comment('Aid or support compliance status');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
