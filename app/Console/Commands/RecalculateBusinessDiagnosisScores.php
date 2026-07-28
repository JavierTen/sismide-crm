<?php

namespace App\Console\Commands;

use App\Models\BusinessDiagnosis;
use Illuminate\Console\Command;

class RecalculateBusinessDiagnosisScores extends Command
{
    protected $signature = 'diagnosis:recalculate-scores';
    protected $description = 'Recalcula los puntajes de todos los diagnósticos empresariales existentes';

    public function handle()
    {
        $this->info('Iniciando recálculo de puntajes de diagnósticos...');

        $diagnoses = BusinessDiagnosis::withTrashed()->get();
        $count = $diagnoses->count();

        if ($count === 0) {
            $this->warn('No se encontraron diagnósticos para recalcular.');
            return 0;
        }

        $this->info("Se encontraron {$count} diagnósticos.");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $updated = 0;
        $errors  = 0;
        $changes = []; // ['Nivel X → Nivel Y' => count]

        foreach ($diagnoses as $diagnosis) {
            try {
                $nivelAntes = $diagnosis->maturity_level ?? 'Sin nivel';

                $diagnosis->total_score = $diagnosis->calculateTotalScore();

                if ($diagnosis->total_score !== null) {
                    $maturity = $diagnosis->getMaturityLevel();
                    $diagnosis->maturity_level = $maturity['label'];
                }

                $nivelDespues = $diagnosis->maturity_level ?? 'Sin nivel';

                if ($nivelAntes !== $nivelDespues) {
                    $key = $nivelAntes . ' → ' . $nivelDespues;
                    $changes[$key] = ($changes[$key] ?? 0) + 1;
                }

                $diagnosis->saveQuietly();
                $updated++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error al recalcular diagnóstico ID {$diagnosis->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✅ Recálculo completado:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total procesados', $count],
                ['Actualizados exitosamente', $updated],
                ['Sin cambios de nivel', $updated - array_sum($changes)],
                ['Con cambio de nivel', array_sum($changes)],
                ['Errores', $errors],
            ]
        );

        if (!empty($changes)) {
            $this->newLine();
            $this->info('📊 Cambios de nivel:');
            arsort($changes);
            $rows = array_map(fn($k, $v) => [$k, $v], array_keys($changes), array_values($changes));
            $this->table(['Antes → Después', 'Registros'], $rows);
        } else {
            $this->info('ℹ️  Ningún registro cambió de nivel.');
        }

        return 0;
    }
}
