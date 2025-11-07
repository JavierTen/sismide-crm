<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pqrfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrepreneur_id')->constrained('entrepreneurs')->onDelete('cascade');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');

            // Campos de la PQRF
            $table->enum('type', ['petition', 'complaint', 'claim', 'congratulation', 'suggestion']);
            $table->text('description');
            $table->date('incident_date');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->json('evidence_files')->nullable(); // Archivos de evidencia del emprendedor

            // Estado
            $table->enum('status', ['pending', 'in_review', 'closed'])->default('pending');

            // Respuesta del gestor
            $table->text('response')->nullable();
            $table->date('response_date')->nullable();
            $table->json('response_files')->nullable(); // Archivos adjuntos en la respuesta
            $table->foreignId('responded_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pqrfs');
    }
};
