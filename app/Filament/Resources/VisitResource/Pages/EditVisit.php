<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Actions;
use App\Models\Visit;
use Filament\Resources\Pages\EditRecord;

class EditVisit extends EditRecord
{
    protected static string $resource = VisitResource::class;

    protected function handleRecordUpdate($record, array $data): Visit
    {
        // Actualizar el registro actual primero
        $record->update([
            'visit_date'        => $data['visit_date'] ?? $record->visit_date,
            'visit_time'        => $data['visit_time'] ?? $record->visit_time,
            'visit_type'        => $data['visit_type'] ?? $record->visit_type,
            'strengthened'      => $data['strengthened'] ?? $record->strengthened,
            'rescheduled'       => $data['rescheduled'] ?? $record->rescheduled,
            'reschedule_reason' => $data['reschedule_reason'] ?? $record->reschedule_reason,
            'manager_id'        => auth()->id(),
        ]);

        // Si en edición se marca rescheduled y se proporcionan datos de new_visit, crear histórico
        if (! empty($data['rescheduled']) && ! empty($data['new_visit_date']) && ! empty($data['new_visit_time'])) {
            $new = Visit::create([
                'entrepreneur_id'   => $record->entrepreneur_id,
                'visit_date'        => $data['new_visit_date'],
                'visit_time'        => $data['new_visit_time'],
                'visit_type'        => $data['new_visit_type'] ?? $record->visit_type,
                'strengthened'      => false,
                'rescheduled'       => false,
                'reschedule_reason' => null,
                'original_visit_id' => $record->id,
                'manager_id'        => auth()->id(),
            ]);

            $record->update(['status' => 'no_show']);
        }

        return $record->fresh();
    }

    public function mount(int | string $record): void
    {
        abort_unless(auth()->user()->can('editVisit'), 403);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->visible(fn () => auth()->user()->can('listVisits')),

            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->can('deleteVisit')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
