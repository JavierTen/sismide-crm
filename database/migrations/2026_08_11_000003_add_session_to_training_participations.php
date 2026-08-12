<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return (bool) DB::select("
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = ? AND index_name = ? LIMIT 1
        ", [$table, $index]);
    }

    public function up(): void
    {
        Schema::table('training_participations', function (Blueprint $table) {
            if (! Schema::hasColumn('training_participations', 'training_session_id')) {
                $table->foreignId('training_session_id')
                    ->nullable()
                    ->after('training_id')
                    ->constrained('training_sessions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('training_participations', 'route_snapshot')) {
                $table->string('route_snapshot', 20)->nullable()->after('attended');
            }
        });

        // El standalone index en training_id permite luego eliminar el unique compuesto
        if (! $this->indexExists('training_participations', 'tp_training_id_idx')) {
            Schema::table('training_participations', function (Blueprint $table) {
                $table->index('training_id', 'tp_training_id_idx');
            });
        }

        if ($this->indexExists('training_participations', 'training_participations_training_id_entrepreneur_id_unique')) {
            Schema::table('training_participations', function (Blueprint $table) {
                $table->dropUnique('training_participations_training_id_entrepreneur_id_unique');
            });
        }

        if (! $this->indexExists('training_participations', 'tp_session_entrepreneur_unique')) {
            Schema::table('training_participations', function (Blueprint $table) {
                $table->unique(['training_session_id', 'entrepreneur_id'], 'tp_session_entrepreneur_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('training_participations', function (Blueprint $table) {
            if ($this->indexExists('training_participations', 'tp_session_entrepreneur_unique')) {
                $table->dropUnique('tp_session_entrepreneur_unique');
            }
            $table->dropForeign(['training_session_id']);
            $table->dropColumn(['training_session_id', 'route_snapshot']);
            $table->unique(['training_id', 'entrepreneur_id']);
        });
    }
};
