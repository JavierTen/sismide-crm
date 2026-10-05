<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de inicios y cierres de sesión de todos los paneles.
 *
 * El usuario es polimórfico porque el panel Emprendedor autentica contra
 * `entrepreneurs` y los demás contra `users`. Nombre y correo se copian al
 * momento del login para que el historial siga legible si el usuario se borra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();

            $table->nullableMorphs('authenticatable');
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            $table->string('panel', 30)->nullable();
            $table->string('guard', 30);
            $table->boolean('via_remember')->default(false);

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('login_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->string('logout_reason', 20)->nullable(); // manual | forced

            $table->timestamps();

            $table->index('login_at');
            $table->index('panel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
