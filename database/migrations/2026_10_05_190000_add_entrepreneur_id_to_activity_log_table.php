<?php

use App\Models\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emprendedor al que se refiere cada acción, resuelto al registrarla. Así se ve
 * en el listado sin consultas extra, sobrevive aunque el registro afectado se
 * elimine definitivamente, y permite ver todo lo que se le hizo a una persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->unsignedBigInteger('entrepreneur_id')->nullable()->after('subject_id');
            $table->index('entrepreneur_id');
        });

        Activity::query()->whereNull('entrepreneur_id')->each(function (Activity $activity): void {
            $id = Activity::resolveEntrepreneurId($activity);

            if ($id) {
                $activity->forceFill(['entrepreneur_id' => $id])->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $table) {
            $table->dropIndex(['entrepreneur_id']);
            $table->dropColumn('entrepreneur_id');
        });
    }
};
