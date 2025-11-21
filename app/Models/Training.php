<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Training extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'city_id',
        'training_date',
        'route',
        'organizer_name',
        'organizer_position',
        'organizer_phone',
        'organizer_entity',
        'organizer_email',
        'modality',
        'ppt_file_path',
        'promotional_file_path',
        'recording_link',
        'objective',
        'manager_id',
    ];

    protected $casts = [
        'training_date' => 'datetime',
    ];

    /**
     * Boot del modelo para manejar eventos
     */
    protected static function booted(): void
    {
        // Eliminar archivos cuando se actualiza el registro
        static::updating(function (Training $training) {
            $originalTraining = $training->getOriginal();

            // Si cambió el archivo PPT, eliminar el anterior
            if (
                $originalTraining['ppt_file_path'] &&
                $originalTraining['ppt_file_path'] !== $training->ppt_file_path
            ) {
                Storage::disk('public')->delete($originalTraining['ppt_file_path']);
            }

            // Si cambió el archivo de divulgación, eliminar el anterior
            if (
                $originalTraining['promotional_file_path'] &&
                $originalTraining['promotional_file_path'] !== $training->promotional_file_path
            ) {
                Storage::disk('public')->delete($originalTraining['promotional_file_path']);
            }
        });

        // Eliminar archivos cuando se elimina el registro (soft delete)
        static::deleting(function (Training $training) {
            if ($training->ppt_file_path) {
                Storage::disk('public')->delete($training->ppt_file_path);
            }

            if ($training->promotional_file_path) {
                Storage::disk('public')->delete($training->promotional_file_path);
            }
        });

        // Eliminar archivos cuando se elimina permanentemente (force delete)
        static::forceDeleting(function (Training $training) {
            if ($training->ppt_file_path) {
                Storage::disk('public')->delete($training->ppt_file_path);
            }

            if ($training->promotional_file_path) {
                Storage::disk('public')->delete($training->promotional_file_path);
            }
        });
    }

    /**
     * Relación con el municipio/ciudad
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Relación con el gestor que registró la capacitación
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Relación con las participaciones (inscripciones a la capacitación)
     */
    public function participations(): HasMany
    {
        return $this->hasMany(TrainingParticipation::class);
    }

    /**
     * Relación con los emprendedores participantes (many-to-many a través de participations)
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            Entrepreneur::class,
            'training_participations',
            'training_id',
            'entrepreneur_id'
        )->withTimestamps()->withTrashed();
    }

    /**
     * Contar participantes de esta capacitación
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->participations()->count();
    }

    /**
     * Obtener el nombre legible de la ruta
     */
    public function getRouteNameAttribute(): string
    {
        return match ($this->route) {
            'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
            'route_2' => 'Ruta 2: Consolidación',
            'route_3' => 'Ruta 3: Escalamiento e Innovación',
            default => $this->route,
        };
    }

    /**
     * Obtener el nombre legible de la modalidad
     */
    public function getModalityNameAttribute(): string
    {
        return match ($this->modality) {
            'virtual' => 'Virtual',
            'in_person' => 'Presencial',
            default => $this->modality,
        };
    }

    /**
     * Verificar si la capacitación es virtual
     */
    public function isVirtual(): bool
    {
        return $this->modality === 'virtual';
    }

    /**
     * Verificar si la capacitación es presencial
     */
    public function isInPerson(): bool
    {
        return $this->modality === 'in_person';
    }

    /**
     * Verificar si tiene archivo PPT
     */
    public function hasPptFile(): bool
    {
        return !empty($this->ppt_file_path);
    }

    /**
     * Verificar si tiene pieza de divulgación
     */
    public function hasPromotionalFile(): bool
    {
        return !empty($this->promotional_file_path);
    }

    /**
     * Verificar si tiene link de grabación
     */
    public function hasRecordingLink(): bool
    {
        return !empty($this->recording_link);
    }

    /**
     * Scope para filtrar por ruta
     */
    public function scopeByRoute($query, string $route)
    {
        return $query->where('route', $route);
    }

    /**
     * Scope para filtrar por modalidad
     */
    public function scopeByModality($query, string $modality)
    {
        return $query->where('modality', $modality);
    }

    /**
     * Scope para filtrar por ciudad
     */
    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope para filtrar por gestor
     */
    public function scopeByManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope para capacitaciones futuras
     */
    public function scopeUpcoming($query)
    {
        return $query->where('training_date', '>=', now());
    }

    /**
     * Scope para capacitaciones pasadas
     */
    public function scopePast($query)
    {
        return $query->where('training_date', '<', now());
    }

    /**
     * Relación con el soporte de evidencias (uno a uno)
     */
    public function support(): HasOne
    {
        return $this->hasOne(TrainingSupport::class);
    }

    /**
     * Verificar si tiene soporte cargado
     */
    public function hasSupportAttribute(): bool
    {
        return $this->support()->exists();
    }
}
