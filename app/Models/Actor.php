<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Actor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Información General
        'name',
        'type',
        'type_other',

        // Contacto Principal
        'contact_name',
        'contact_role',
        'contact_email',
        'contact_phone',

        // Ubicación y Accesibilidad
        'has_physical_office',
        'office_address',
        'city_id',
        'department_id',
        'main_location',
        'office_hours',

        // Georreferenciación
        'latitude',
        'longitude',
        'georeference_photo_path',

        // Áreas de aporte
        'contribution_areas',
        'contribution_areas_other',

        // Experiencias previas
        'has_entrepreneurship_experience',
        'entrepreneurship_experience_details',

        // Compromisos
        'commitments',
        'commitments_other',

        // Utilidad Estratégica
        'market_connection',
        'authority_management',
        'financing_access',
        'training_advisory',
        'logistic_support',

        // Valor Diferencial
        'action_scope',

        // Manager
        'manager_id',
    ];

    protected $casts = [
        'has_physical_office' => 'boolean',
        'has_entrepreneurship_experience' => 'boolean',
        'contribution_areas' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Al actualizar: eliminar foto antigua si se cambió
        static::updating(function ($actor) {
            if ($actor->isDirty('georeference_photo_path')) {
                $oldPhoto = $actor->getOriginal('georeference_photo_path');

                if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }
        });

        // Al eliminar (soft delete): NO eliminar la foto aún
        static::deleting(function ($actor) {
            // No hacer nada en soft delete
        });

        // Al forzar eliminación: eliminar la foto permanentemente
        static::forceDeleting(function ($actor) {
            if ($actor->georeference_photo_path && Storage::disk('public')->exists($actor->georeference_photo_path)) {
                Storage::disk('public')->delete($actor->georeference_photo_path);
            }
        });

        // Al restaurar: no hacer nada (la foto ya existe)
        static::restoring(function ($actor) {
            // La foto se mantiene
        });
    }

    // Relaciones
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Constantes para los enums
    public const TYPE_OPTIONS = [
        'private_company' => 'Empresa privada',
        'commercial' => 'Comercial',
        'guild_association' => 'Asociación gremial',
        'government_authority' => 'Autoridad gubernamental',
        'educational_institution' => 'Institución educativa',
        'ngo_foundation' => 'ONG / Fundación',
        'financial_entity' => 'Entidad financiera',
        'other' => 'Otro',
    ];

    public const CONTRIBUTION_AREAS_OPTIONS = [
        'institutional_support' => 'Apoyo institucional',
        'training_capacity' => 'Formación y fortalecimiento de capacidades',
        'financial_support' => 'Apoyo financiero',
        'commercialization' => 'Comercialización',
        'communication_dissemination' => 'Comunicación y difusión',
        'network_linkage' => 'Vinculación a redes o ecosistemas',
        'other' => 'Otro',
    ];

    public const COMMITMENTS_OPTIONS = [
        'financial_resources' => 'Aportar recursos financieros',
        'in_kind_resources' => 'Aportar recursos en especie',
        'training_technical' => 'Brindar formación / asistencia técnica',
        'strategic_contacts' => 'Facilitar contactos estratégicos',
        'other' => 'Otro',
    ];

    public const ACTION_SCOPE_OPTIONS = [
        'local' => 'Local (solo municipio)',
        'regional' => 'Regional (Magdalena u otros municipios)',
        'national' => 'Nacional',
        'international' => 'Internacional',
    ];

    // Scopes
    public function scopeByManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeWithPhysicalOffice($query)
    {
        return $query->where('has_physical_office', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByActionScope($query, string $scope)
    {
        return $query->where('action_scope', $scope);
    }

    // Accessors
    public function getTypeNameAttribute(): string
    {
        return self::TYPE_OPTIONS[$this->type] ?? $this->type;
    }

    public function getActionScopeNameAttribute(): string
    {
        return self::ACTION_SCOPE_OPTIONS[$this->action_scope] ?? $this->action_scope;
    }
}
