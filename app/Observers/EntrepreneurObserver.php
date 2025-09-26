<?php

namespace App\Observers;

use App\Models\Entrepreneur;

class EntrepreneurObserver
{
    public function deleting(Entrepreneur $entrepreneur)
    {
        // Solo hacer soft delete en cascada si es soft delete (no force delete)
        if (!$entrepreneur->isForceDeleting()) {
            // Soft delete en cascada para visitas
            $entrepreneur->visits()->delete();

            // Soft delete en cascada para caracterizaciones
            $entrepreneur->characterizations()->delete();

            // Soft delete en cascada para diagnósticos
            $entrepreneur->businessDiagnoses()->delete();
        }
        // Si es forceDelete(), las foreign keys cascade harán el hard delete automáticamente
    }

    public function restoring(Entrepreneur $entrepreneur)
    {
        // Restaurar registros relacionados cuando se restaure el emprendedor
        $entrepreneur->visits()->withTrashed()->where('deleted_at', '!=', null)->restore();
        $entrepreneur->characterizations()->withTrashed()->where('deleted_at', '!=', null)->restore();
        $entrepreneur->businessDiagnoses()->withTrashed()->where('deleted_at', '!=', null)->restore();
    }
}
