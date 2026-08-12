<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Actor;
use App\Models\EntityContact;

class Training extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        'name',
        'city_id',
        'training_date',
        'start_time',
        'end_time',
        'intensity_hours',
        'route',
        'status',
        'organizer_name',
        'organizer_position',
        'organizer_phone',
        'organizer_entity',
        'organizer_email',
        'actor_id',
        'entity_contact_id',
        'modality',
        'location',
        'ppt_file_path',
        'promotional_file_path',
        'recording_link',
        'objective',
        'manager_id',
        'updated_by_id',
    ];

    protected $casts = [
        'training_date' => 'date',
        'intensity_hours' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));

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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled'          => 'Programada',
            'attendance_pending' => 'Pendiente de Asistencia',
            'support_pending'    => 'Pendiente de Soportes',
            'complete'           => 'Completa',
            default              => $this->status ?? 'Programada',
        };
    }

    public function computeStatus(): string
    {
        if ($this->participations()->count() === 0) {
            return $this->training_date?->isFuture() ? 'scheduled' : 'attendance_pending';
        }

        $support = $this->support;

        if (! $support) {
            return 'support_pending';
        }

        $hasEvidence = match ($this->modality) {
            'virtual'   => $support->connection_evidence_path && $support->visual_evidence_path,
            'in_person' => $support->attendance_list_path && ! empty($support->photos) && count($support->photos) >= 2,
            'hybrid'    => $support->attendance_list_path
                           && ! empty($support->photos) && count($support->photos) >= 2
                           && $support->connection_evidence_path && $support->visual_evidence_path,
            default     => true,
        };

        return $hasEvidence ? 'complete' : 'support_pending';
    }

    public function syncStatus(): void
    {
        $this->update(['status' => $this->computeStatus()]);
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function entityContact(): BelongsTo
    {
        return $this->belongsTo(EntityContact::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
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
