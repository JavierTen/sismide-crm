<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityContact extends Model
{
    use SoftDeletes;

    protected $table = 'entity_contacts';

    protected $fillable = [
        'entity_id',
        'name',
        'role',
        'email',
        'phone',
        'area',
        'contact_type',
        'decision_level',
        'is_primary_contact',
        'status',
        'notes',
        'manager_id',
    ];

    protected $casts = [
        'is_primary_contact' => 'boolean',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Actor::class, 'entity_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public const CONTACT_TYPE_OPTIONS = [
        'directive'    => 'Directivo',
        'institutional'=> 'Institucional',
        'commercial'   => 'Comercial',
        'financial'    => 'Financiero',
        'technical'    => 'Técnico',
        'formative'    => 'Formativo',
        'logistic'     => 'Logístico',
        'other'        => 'Otro',
    ];

    public const DECISION_LEVEL_OPTIONS = [
        'decision_maker' => 'Toma decisiones',
        'influencer'     => 'Influye en la decisión',
        'operational'    => 'Contacto operativo',
    ];

    public const STATUS_OPTIONS = [
        'active'          => 'Activo',
        'pending_contact' => 'Pendiente de contactar',
        'no_response'     => 'No responde',
        'withdrawn'       => 'Retirado de la entidad',
        'inactive'        => 'Inactivo',
    ];
}
