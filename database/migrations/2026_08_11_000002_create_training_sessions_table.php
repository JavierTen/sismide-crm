<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->date('session_date');
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Una capacitación no puede ejecutarse dos veces el mismo día en el mismo municipio
            $table->unique(['training_id', 'city_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
