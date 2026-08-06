<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('confirmed_by_id')
                ->nullable()
                ->after('updated_by_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('confirmed_at')
                ->nullable()
                ->after('confirmed_by_id');
        });

        // Solo visitas que ya tienen resultado: fecha = updated_at, usuario = quien la creó (manager_id)
        DB::statement("
            UPDATE visits
            SET confirmed_at    = updated_at,
                confirmed_by_id = manager_id
            WHERE visit_result IS NOT NULL
              AND visit_result  != ''
        ");
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by_id');
            $table->dropColumn('confirmed_at');
        });
    }
};
