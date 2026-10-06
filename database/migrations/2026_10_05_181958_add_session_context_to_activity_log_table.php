<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto propio sobre la tabla de spatie/laravel-activitylog: cada acción
 * queda ligada a la sesión del registro de sesiones en que ocurrió, para poder
 * responder "qué hizo esta persona mientras estuvo conectada".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->unsignedBigInteger('login_log_id')->nullable()->after('causer_id');
            $table->string('panel', 30)->nullable()->after('login_log_id');
            $table->string('ip_address', 45)->nullable()->after('panel');

            $table->foreign('login_log_id', 'activity_log_login_log_fk')
                ->references('id')->on('login_logs')->nullOnDelete();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropForeign('activity_log_login_log_fk');
            $table->dropIndex(['created_at']);
            $table->dropColumn(['login_log_id', 'panel', 'ip_address']);
        });
    }
};
