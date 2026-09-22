<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_fairs', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('location');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->date('start_date');
            $table->date('end_date');

            $table->string('organizer_name')->nullable();
            $table->string('organizer_position')->nullable();
            $table->string('organizer_phone', 20)->nullable();
            $table->string('organizer_email')->nullable();

            $table->text('observations')->nullable();

            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fairs');
    }
};
