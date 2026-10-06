<?php

namespace App\Observers;

use App\Models\Entrepreneur;
use App\Support\AuditTrail;

class EntrepreneurObserver
{
    public function deleting(Entrepreneur $entrepreneur)
    {
        // Solo hacer soft delete en cascada si es soft delete (no force delete)
        if (!$entrepreneur->isForceDeleting()) {
            // Los borrados masivos no disparan eventos de modelo: se cuenta lo que
            // realmente se afecta, con las mismas consultas, para el historial.
            $counts = [
                'visits'            => $entrepreneur->visits()->count(),
                'characterizations' => $entrepreneur->characterizations()->count(),
                'diagnoses'         => $entrepreneur->businessDiagnoses()->count(),
            ];

            // Soft delete en cascada para visitas
            $entrepreneur->visits()->delete();

            // Soft delete en cascada para caracterizaciones
            $entrepreneur->characterizations()->delete();

            // Soft delete en cascada para diagnósticos
            $entrepreneur->businessDiagnoses()->delete();

            AuditTrail::cascade($entrepreneur, 'cascade_deleted', $this->labelled($counts));
        }
        // Si es forceDelete(), las foreign keys cascade harán el hard delete automáticamente
    }

    public function restoring(Entrepreneur $entrepreneur)
    {
        $counts = [
            'visits'            => $entrepreneur->visits()->onlyTrashed()->count(),
            'characterizations' => $entrepreneur->characterizations()->onlyTrashed()->count(),
            'diagnoses'         => $entrepreneur->businessDiagnoses()->onlyTrashed()->count(),
        ];

        // Restaurar registros relacionados cuando se restaure el emprendedor
        $entrepreneur->visits()->withTrashed()->where('deleted_at', '!=', null)->restore();
        $entrepreneur->characterizations()->withTrashed()->where('deleted_at', '!=', null)->restore();
        $entrepreneur->businessDiagnoses()->withTrashed()->where('deleted_at', '!=', null)->restore();

        AuditTrail::cascade($entrepreneur, 'cascade_restored', $this->labelled($counts));
    }

    /**
     * @param  array{visits: int, characterizations: int, diagnoses: int}  $counts
     * @return array<string, int>
     */
    private function labelled(array $counts): array
    {
        return [
            ($counts['visits'] === 1 ? 'visita' : 'visitas')                                => $counts['visits'],
            ($counts['characterizations'] === 1 ? 'caracterización' : 'caracterizaciones') => $counts['characterizations'],
            ($counts['diagnoses'] === 1 ? 'diagnóstico' : 'diagnósticos')                   => $counts['diagnoses'],
        ];
    }
}
