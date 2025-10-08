<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fairs', function (Blueprint $table) {
            $table->id();

            // 1. Información General de la Feria
            $table->string('name', 100);
            $table->string('location', 50);
            $table->text('address');
            $table->date('start_date');
            $table->date('end_date');

            // 2. Organización y Propiedad
            $table->string('organizer_name', 100);
            $table->string('organizer_position', 100);
            $table->string('organizer_phone', 50);
            $table->string('organizer_email', 100);

            // 3. Observaciones
            $table->text('observations')->nullable();

            // Manager que registró
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('start_date');
            $table->index('end_date');
            $table->index('location');
            $table->index('manager_id');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fairs');
    }
};
