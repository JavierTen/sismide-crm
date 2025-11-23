<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            // Agregar nuevos campos
            $table->date('training_date_only')->nullable()->after('training_date');
            $table->time('start_time')->nullable()->after('training_date_only');
            $table->time('end_time')->nullable()->after('start_time');
            $table->decimal('intensity_hours', 5, 2)->nullable()->after('end_time')->comment('Intensidad horaria en horas');
        });

        // Migrar datos existentes de training_date a los nuevos campos
        DB::table('trainings')->get()->each(function ($training) {
            if ($training->training_date) {
                $dateTime = \Carbon\Carbon::parse($training->training_date);
                DB::table('trainings')
                    ->where('id', $training->id)
                    ->update([
                        'training_date_only' => $dateTime->toDateString(),
                        'start_time' => $dateTime->toTimeString(),
                    ]);
            }
        });

        Schema::table('trainings', function (Blueprint $table) {
            // Eliminar el campo antiguo
            $table->dropColumn('training_date');
        });

        Schema::table('trainings', function (Blueprint $table) {
            // Renombrar el campo nuevo y hacerlo required
            $table->renameColumn('training_date_only', 'training_date');
        });

        // Modificar el enum de modality para incluir 'hybrid'
        DB::statement("ALTER TABLE trainings MODIFY COLUMN modality ENUM('virtual', 'in_person', 'hybrid') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Crear campo training_date como datetime
        Schema::table('trainings', function (Blueprint $table) {
            $table->dateTime('training_date_temp')->nullable()->after('city_id');
        });

        // Migrar datos de vuelta
        DB::table('trainings')->get()->each(function ($training) {
            if ($training->training_date && $training->start_time) {
                $dateTime = \Carbon\Carbon::parse($training->training_date . ' ' . $training->start_time);
                DB::table('trainings')
                    ->where('id', $training->id)
                    ->update([
                        'training_date_temp' => $dateTime,
                    ]);
            }
        });

        Schema::table('trainings', function (Blueprint $table) {
            // Eliminar nuevos campos
            $table->dropColumn(['training_date', 'start_time', 'end_time', 'intensity_hours']);
        });

        Schema::table('trainings', function (Blueprint $table) {
            // Renombrar el campo temporal
            $table->renameColumn('training_date_temp', 'training_date');
        });

        // Restaurar enum original
        DB::statement("ALTER TABLE trainings MODIFY COLUMN modality ENUM('virtual', 'in_person') NOT NULL");
    }
};
