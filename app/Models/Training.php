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
        'training_date',      // ← Ahora es solo fecha (date)
        'start_time',         // ← NUEVO
        'end_time',           // ← NUEVO
        'intensity_hours',    // ← NUEVO
        'route',
        'organizer_name',
        'organizer_position',
        'organizer_phone',
        'organizer_entity',
        'organizer_email',
        'modality',           // ← Ahora incluye 'hybrid'
        'ppt_file_path',
        'promotional_file_path',
        'recording_link',
        'objective',
        'manager_id',
    ];

    protected $casts = [
        'training_date' => 'date',
        'intensity_hours' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::updating(function (Training $training) {
            $originalTraining = $training->getOriginal();

            if (
                $originalTraining['ppt_file_path'] &&
                $originalTraining['ppt_file_path'] !== $training->ppt_file_path
            ) {
                Storage::disk('public')->delete($originalTraining['ppt_file_path']);
            }

            if (
                $originalTraining['promotional_file_path'] &&
                $originalTraining['promotional_file_path'] !== $training->promotional_file_path
            ) {
                Storage::disk('public')->delete($originalTraining['promotional_file_path']);
            }
        });

        static::deleting(function (Training $training) {
            if ($training->ppt_file_path) {
                Storage::disk('public')->delete($training->ppt_file_path);
            }

            if ($training->promotional_file_path) {
                Storage::disk('public')->delete($training->promotional_file_path);
            }
        });

        static::forceDeleting(function (Training $training) {
            if ($training->ppt_file_path) {
                Storage::disk('public')->delete($training->ppt_file_path);
            }

            if ($training->promotional_file_path) {
                Storage::disk('public')->delete($training->promotional_file_path);
            }
        });
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(TrainingParticipation::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            Entrepreneur::class,
            'training_participations',
            'training_id',
            'entrepreneur_id'
        )->withTimestamps()->withTrashed();
    }

    public function getParticipantsCountAttribute(): int
    {
        return $this->participations()->count();
    }

    public function getRouteNameAttribute(): string
    {
        return match ($this->route) {
            'route_1' => 'Ruta 1: Pre-emprendimiento y validación temprana',
            'route_2' => 'Ruta 2: Consolidación',
            'route_3' => 'Ruta 3: Escalamiento e Innovación',
            default => $this->route,
        };
    }

    public function getModalityNameAttribute(): string
    {
        return match ($this->modality) {
            'virtual' => 'Virtual',
            'in_person' => 'Presencial',
            'hybrid' => 'Híbrida',
            default => $this->modality,
        };
    }

    public function getFormattedDateTimeAttribute(): string
    {
        $date = $this->training_date?->format('d/m/Y') ?? '';
        $time = $this->start_time ? ' ' . substr($this->start_time, 0, 5) : '';
        return $date . $time;
    }

    public function getTimeRangeAttribute(): string
    {
        if (!$this->start_time) return 'Sin horario';

        $start = substr($this->start_time, 0, 5);
        $end = $this->end_time ? ' - ' . substr($this->end_time, 0, 5) : '';

        return $start . $end;
    }

    public function isVirtual(): bool
    {
        return $this->modality === 'virtual';
    }

    public function isInPerson(): bool
    {
        return $this->modality === 'in_person';
    }

    public function isHybrid(): bool
    {
        return $this->modality === 'hybrid';
    }

    public function hasPptFile(): bool
    {
        return !empty($this->ppt_file_path);
    }

    public function hasPromotionalFile(): bool
    {
        return !empty($this->promotional_file_path);
    }

    public function hasRecordingLink(): bool
    {
        return !empty($this->recording_link);
    }

    public function scopeByRoute($query, string $route)
    {
        return $query->where('route', $route);
    }

    public function scopeByModality($query, string $modality)
    {
        return $query->where('modality', $modality);
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeByManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('training_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('training_date', '<', now()->toDateString());
    }

    public function support(): HasOne
    {
        return $this->hasOne(TrainingSupport::class);
    }

    public function hasSupportAttribute(): bool
    {
        return $this->support()->exists();
    }
}
