<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillUpdatedBy extends Command
{
    protected $signature   = 'db:backfill-updated-by';
    protected $description = 'Rellena updated_by_id con el creador conocido de cada registro existente';

    public function handle(): int
    {
        $this->info('Iniciando backfill de updated_by_id...');

        // Emprendedores: creados por el usuario registrado en user_id
        $this->backfillFromField('entrepreneurs', 'user_id');

        // Negocios: heredan el user_id del emprendedor dueño
        $count = DB::table('businesses as b')
            ->join('entrepreneurs as e', 'b.entrepreneur_id', '=', 'e.id')
            ->whereNull('b.updated_by_id')
            ->whereNotNull('e.user_id')
            ->update(['b.updated_by_id' => DB::raw('e.user_id')]);
        $this->line("  businesses: {$count} registros actualizados desde entrepreneurs.user_id");

        // Visitas, caracterizaciones y diagnósticos: responsable es manager_id
        $this->backfillFromField('visits', 'manager_id');
        $this->backfillFromField('characterizations', 'manager_id');
        $this->backfillFromField('business_diagnoses', 'manager_id');

        $this->info('Backfill completado.');
        $this->warn('Registros sin creador conocido conservan updated_by_id = null.');

        return 0;
    }

    private function backfillFromField(string $table, string $sourceField): void
    {
        $count = DB::table($table)
            ->whereNull('updated_by_id')
            ->whereNotNull($sourceField)
            ->update(['updated_by_id' => DB::raw($sourceField)]);

        $this->line("  {$table}: {$count} registros actualizados desde {$sourceField}");
    }
}
