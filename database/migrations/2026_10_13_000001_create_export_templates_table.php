<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de la exportación por cláusulas: guardan la cláusula, los
 * municipios, las columnas en su orden y las condiciones, para repetir un
 * mismo Excel con un clic.
 *
 * Públicas: las ve cualquiera con acceso al módulo. Privadas: solo su creador.
 * En ambos casos solo el creador puede modificarlas o eliminarlas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->json('config');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_templates');
    }
};
