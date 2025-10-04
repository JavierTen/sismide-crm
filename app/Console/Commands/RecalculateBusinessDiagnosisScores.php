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
        $errors = 0;

        foreach ($diagnoses as $diagnosis) {
            try {
                $diagnosis->total_score = $diagnosis->calculateTotalScore();

                // Calcular y guardar el nivel de madurez
                if ($diagnosis->total_score !== null) {
                    $maturity = $diagnosis->getMaturityLevel();
                    $diagnosis->maturity_level = $maturity['label'];
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

        $this->info("✅ Recálculo completado:");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total procesados', $count],
                ['Actualizados exitosamente', $updated],
                ['Errores', $errors],
            ]
        );

        return 0;
    }
}
