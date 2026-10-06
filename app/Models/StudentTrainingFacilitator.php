<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pareja entidad + capacitador, ambos tomados de la base de Ruta D.
 * No usa SoftDeletes: es un detalle de composición de la capacitación.
 */
class StudentTrainingFacilitator extends Model
{
    protected $fillable = [
        'student_training_id',
        'actor_id',
        'entity_contact_id',
    ];

    /**
     * Los cambios de facilitadores se registran sobre la capacitación, no como
     * un módulo aparte: así aparecen en el historial de la capacitación.
     */
    protected static function booted(): void
    {
        static::created(fn (self $facilitator) => $facilitator->auditChange(added: [$facilitator->label()]));

        static::deleted(fn (self $facilitator) => $facilitator->auditChange(removed: [$facilitator->label()]));

        static::updated(function (self $facilitator): void {
            if (! $facilitator->wasChanged(['actor_id', 'entity_contact_id'])) {
                return;
            }

            $facilitator->auditChange(
                added: [$facilitator->label()],
                removed: [$facilitator->label(
                    $facilitator->getOriginal('actor_id'),
                    $facilitator->getOriginal('entity_contact_id'),
                )],
            );
        });
    }

    /**
     * @param  array<int, string>  $added
     * @param  array<int, string>  $removed
     */
    private function auditChange(array $added = [], array $removed = []): void
    {
        try {
            $training = StudentTraining::withoutGlobalScopes()->find($this->student_training_id);

            if (! $training) {
                return;
            }

            $total = static::where('student_training_id', $training->getKey())->count();

            activity('relation')
                ->performedOn($training)
                ->event('relation_updated')
                ->withProperties([
                    'relation' => 'facilitadores',
                    'total'    => $total,
                    'added'    => $added,
                    'removed'  => $removed,
                ])
                ->log(sprintf('Actualizó facilitadores: +%d / -%d (quedan %d)', count($added), count($removed), $total));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /** "Capacitador (Entidad)", con los ids dados o los actuales. */
    private function label(mixed $actorId = null, mixed $contactId = null): string
    {
        $actorId   ??= $this->actor_id;
        $contactId ??= $this->entity_contact_id;

        $contact = EntityContact::withoutGlobalScopes()->find($contactId)?->name ?? "Capacitador #{$contactId}";
        $entity  = Actor::withoutGlobalScopes()->find($actorId)?->name ?? "Entidad #{$actorId}";

        return "{$contact} ({$entity})";
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(StudentTraining::class, 'student_training_id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'actor_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(EntityContact::class, 'entity_contact_id');
    }
}
