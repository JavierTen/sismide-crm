<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_participations', function (Blueprint $table) {
            // Verificar que el unique compuesto aún exista antes de eliminarlo
            $indexExists = DB::select("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'training_participations'
                  AND index_name = 'training_participations_training_id_entrepreneur_id_unique'
                LIMIT 1
            ");

            if ($indexExists) {
                $table->dropUnique('training_participations_training_id_entrepreneur_id_unique');
            }

            // Verificar que el nuevo unique no exista ya
            $newIndexExists = DB::select("
                SELECT 1
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'training_participations'
                  AND index_name = 'tp_session_entrepreneur_unique'
                LIMIT 1
            ");

            if (! $newIndexExists) {
                $table->unique(['training_session_id', 'entrepreneur_id'], 'tp_session_entrepreneur_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('training_participations', function (Blueprint $table) {
            $table->dropUnique('tp_session_entrepreneur_unique');
            $table->unique(['training_id', 'entrepreneur_id']);
        });
    }
};
