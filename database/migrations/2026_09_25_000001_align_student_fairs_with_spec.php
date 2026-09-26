<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alinea el esquema de Ferias Estudiantiles con la especificación funcional:
 * los campos marcados como obligatorios pasan a NOT NULL y la dirección
 * exacta pasa de VARCHAR a TEXT (el documento la define como "texto largo").
 *
 * Se usa SQL directo porque el proyecto no incluye doctrine/dbal, requerido
 * por ->change() en Laravel 10.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE student_fairs MODIFY address TEXT NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY latitude DECIMAL(10,8) NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY longitude DECIMAL(11,8) NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_position VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_phone VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY observations TEXT NOT NULL');

        DB::statement('ALTER TABLE student_fair_participations MODIFY organization_rating VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE student_fair_participations MODIFY visitor_flow VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE student_fair_participations MODIFY description TEXT NOT NULL');
        DB::statement('ALTER TABLE student_fair_participations MODIFY attendance_list_path VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE student_fairs MODIFY address VARCHAR(255) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY latitude DECIMAL(10,8) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY longitude DECIMAL(11,8) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_name VARCHAR(255) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_position VARCHAR(255) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_phone VARCHAR(20) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY organizer_email VARCHAR(255) NULL');
        DB::statement('ALTER TABLE student_fairs MODIFY observations TEXT NULL');

        DB::statement('ALTER TABLE student_fair_participations MODIFY organization_rating VARCHAR(255) NULL');
        DB::statement('ALTER TABLE student_fair_participations MODIFY visitor_flow VARCHAR(255) NULL');
        DB::statement('ALTER TABLE student_fair_participations MODIFY description TEXT NULL');
        DB::statement('ALTER TABLE student_fair_participations MODIFY attendance_list_path VARCHAR(255) NULL');
    }
};
