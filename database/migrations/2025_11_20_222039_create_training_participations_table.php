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
        Schema::create('training_participations', function (Blueprint $table) {
            $table->id();

            // Relación con la capacitación
            $table->foreignId('training_id')->constrained('trainings')->onDelete('cascade');

            // Relación con el emprendedor
            $table->foreignId('entrepreneur_id')->constrained('entrepreneurs')->onDelete('cascade');

            // Gestor que registra la participación
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Evitar duplicados: un emprendedor no puede estar registrado dos veces en la misma capacitación
            $table->unique(['training_id', 'entrepreneur_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_participations');
    }
};
