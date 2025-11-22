<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingParticipation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'entrepreneur_id',
        'manager_id',
        'attended',                    // ← NUEVO
        'non_attendance_reason',
    ];

    protected $casts = [
        'attended' => 'boolean',
    ];

    /**
     * Relación con la capacitación
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Relación con el emprendedor
     */
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    /**
     * Relación con el gestor que registró la participación
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Scope para filtrar por capacitación
     */
    public function scopeByTraining($query, int $trainingId)
    {
        return $query->where('training_id', $trainingId);
    }

    /**
     * Scope para filtrar por emprendedor
     */
    public function scopeByEntrepreneur($query, int $entrepreneurId)
    {
        return $query->where('entrepreneur_id', $entrepreneurId);
    }

    /**
     * Scope para filtrar por gestor
     */
    public function scopeByManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope para filtrar por ruta de capacitación
     */
    public function scopeByRoute($query, string $route)
    {
        return $query->whereHas('training', function ($q) use ($route) {
            $q->where('route', $route);
        });
    }

    /**
     * Scope para filtrar por ciudad
     */
    public function scopeByCity($query, int $cityId)
    {
        return $query->whereHas('entrepreneur', function ($q) use ($cityId) {
            $q->where('city_id', $cityId);
        });
    }

    /**
     * Verificar si la participación es de una capacitación virtual
     */
    public function isVirtualTraining(): bool
    {
        return $this->training?->isVirtual() ?? false;
    }

    /**
     * Verificar si la participación es de una capacitación presencial
     */
    public function isInPersonTraining(): bool
    {
        return $this->training?->isInPerson() ?? false;
    }

    /**
     * Obtener el nombre completo del emprendedor
     */
    public function getEntrepreneurFullNameAttribute(): string
    {
        return $this->entrepreneur?->full_name ?? 'Sin emprendedor';
    }

    /**
     * Obtener el nombre de la capacitación
     */
    public function getTrainingNameAttribute(): string
    {
        return $this->training?->name ?? 'Sin capacitación';
    }

    /**
     * Obtener la ruta de la capacitación
     */
    public function getTrainingRouteAttribute(): string
    {
        return $this->training?->route_name ?? 'Sin ruta';
    }

    /**
     * Verificar si existe una participación para evitar duplicados
     */
    public static function exists(int $trainingId, int $entrepreneurId): bool
    {
        return static::where('training_id', $trainingId)
            ->where('entrepreneur_id', $entrepreneurId)
            ->exists();
    }

    /**
     * Contar participantes de una capacitación
     */
    public static function countByTraining(int $trainingId): int
    {
        return static::where('training_id', $trainingId)->count();
    }

    /**
     * Contar capacitaciones de un emprendedor
     */
    public static function countByEntrepreneur(int $entrepreneurId): int
    {
        return static::where('entrepreneur_id', $entrepreneurId)->count();
    }
}


