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
