<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Actions;
use App\Models\Visit;
use Filament\Resources\Pages\CreateRecord;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    protected function handleRecordCreation(array $data): Visit
    {
        // Crear la visita principal (la que se muestra en el formulario)
        $visit = Visit::create([
            'entrepreneur_id'   => $data['entrepreneur_id'] ?? null,
            'visit_date'        => $data['visit_date'] ?? null,
            'visit_time'        => $data['visit_time'] ?? null,
            'visit_type'        => $data['visit_type'] ?? null,
            'strengthened'      => $data['strengthened'] ?? false,
            'rescheduled'       => $data['rescheduled'] ?? false,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
        ]);

        // Si se solicitó reagendar, crear la visita reagendada como histórico
        if (! empty($data['rescheduled']) && ! empty($data['new_visit_date']) && ! empty($data['new_visit_time'])) {
            $rescheduledVisit = Visit::create([
                'entrepreneur_id'   => $visit->entrepreneur_id,
                'visit_date'        => $data['new_visit_date'],
                'visit_time'        => $data['new_visit_time'],
                'visit_type'        => $data['new_visit_type'] ?? $visit->visit_type,
                'strengthened'      => false,
                'rescheduled'       => false,
                'reschedule_reason' => null,
                'original_visit_id' => $visit->id,
            ]);

            // Opcional: actualizar el status del original (por ejemplo 'no_show' o 'rescheduled')
            $visit->update(['status' => 'no_show']); // o 'rescheduled' según tu lógica
        }

        return $visit;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->can('createVisit'), 403);
        parent::mount();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
