<?php

use App\Models\Entrepreneur;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia de los roles con los que el usuario inició sesión. Se guarda en el
 * momento del login para que el historial no cambie si después le asignan
 * otro rol. JSON porque un usuario puede tener varios roles a la vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->json('user_roles')->nullable()->after('user_email');
        });

        // Los registros previos son de recién desplegada la funcionalidad, así
        // que el rol actual del usuario es el mismo con el que entró.
        LoginLog::query()->whereNull('user_roles')->each(function (LoginLog $log): void {
            $roles = match ($log->authenticatable_type) {
                Entrepreneur::class => ['Emprendedor'],
                User::class         => User::find($log->authenticatable_id)?->getRoleNames()->values()->all() ?? [],
                default             => [],
            };

            $log->forceFill(['user_roles' => $roles])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropColumn('user_roles');
        });
    }
};
