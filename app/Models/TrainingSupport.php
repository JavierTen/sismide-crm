<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class TrainingSupport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'attendance_list_path',
        'recording_link',
        'georeference_photo_path',
        'additional_photo_1_path',
        'additional_photo_2_path',
        'additional_photo_3_path',
        'observations',
        'manager_id',
    ];

    /**
     * Boot del modelo para manejar eventos
     */
    protected static function booted(): void
    {
        // Eliminar archivos cuando se actualiza el registro
        static::updating(function (TrainingSupport $support) {
            $original = $support->getOriginal();

            // Lista de campos de archivos a verificar
            $fileFields = [
                'attendance_list_path',
                'georeference_photo_path',
                'additional_photo_1_path',
                'additional_photo_2_path',
                'additional_photo_3_path',
            ];

            foreach ($fileFields as $field) {
                if ($original[$field] && $original[$field] !== $support->$field) {
                    Storage::disk('public')->delete($original[$field]);
                }
            }
        });

        // Eliminar archivos cuando se elimina el registro (soft delete)
        static::deleting(function (TrainingSupport $support) {
            $filesToDelete = [
                $support->attendance_list_path,
                $support->georeference_photo_path,
                $support->additional_photo_1_path,
                $support->additional_photo_2_path,
                $support->additional_photo_3_path,
            ];

            foreach ($filesToDelete as $file) {
                if ($file) {
                    Storage::disk('public')->delete($file);
                }
            }
        });

        // Eliminar archivos cuando se elimina permanentemente (force delete)
        static::forceDeleting(function (TrainingSupport $support) {
            $filesToDelete = [
                $support->attendance_list_path,
                $support->georeference_photo_path,
                $support->additional_photo_1_path,
                $support->additional_photo_2_path,
                $support->additional_photo_3_path,
            ];

            foreach ($filesToDelete as $file) {
                if ($file) {
                    Storage::disk('public')->delete($file);
                }
            }
        });
    }

    /**
     * Relación con la capacitación
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Relación con el gestor que registró el soporte
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Verificar si la capacitación es virtual
     */
    public function isVirtualTraining(): bool
    {
        return $this->training?->isVirtual() ?? false;
    }

    /**
     * Verificar si la capacitación es presencial
     */
    public function isInPersonTraining(): bool
    {
        return $this->training?->isInPerson() ?? false;
    }

    /**
     * Obtener la modalidad de la capacitación
     */
    public function getTrainingModalityAttribute(): string
    {
        return $this->training?->modality_name ?? 'Sin modalidad';
    }

    /**
     * Obtener el nombre de la capacitación
     */
    public function getTrainingNameAttribute(): string
    {
        return $this->training?->name ?? 'Sin capacitación';
    }

    /**
     * Obtener el municipio de la capacitación
     */
    public function getTrainingCityAttribute(): string
    {
        return $this->training?->city?->name ?? 'Sin municipio';
    }

    /**
     * Verificar si tiene lista de asistencia
     */
    public function hasAttendanceList(): bool
    {
        return !empty($this->attendance_list_path);
    }

    /**
     * Verificar si tiene link de grabación
     */
    public function hasRecordingLink(): bool
    {
        return !empty($this->recording_link);
    }

    /**
     * Verificar si tiene foto de georreferenciación
     */
    public function hasGeoreferencePhoto(): bool
    {
        return !empty($this->georeference_photo_path);
    }

    /**
     * Contar fotos adicionales
     */
    public function getAdditionalPhotosCountAttribute(): int
    {
        $count = 0;
        if ($this->additional_photo_1_path) $count++;
        if ($this->additional_photo_2_path) $count++;
        if ($this->additional_photo_3_path) $count++;
        return $count;
    }

    /**
     * Obtener todas las fotos adicionales en un array
     */
    public function getAdditionalPhotosAttribute(): array
    {
        return array_filter([
            $this->additional_photo_1_path,
            $this->additional_photo_2_path,
            $this->additional_photo_3_path,
        ]);
    }

    /**
     * Scope para filtrar por capacitación
     */
    public function scopeByTraining($query, int $trainingId)
    {
        return $query->where('training_id', $trainingId);
    }

    /**
     * Scope para filtrar por gestor
     */
    public function scopeByManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    /**
     * Scope para soportes de capacitaciones virtuales
     */
    public function scopeVirtualTrainings($query)
    {
        return $query->whereHas('training', function ($q) {
            $q->where('modality', 'virtual');
        });
    }

    /**
     * Scope para soportes de capacitaciones presenciales
     */
    public function scopeInPersonTrainings($query)
    {
        return $query->whereHas('training', function ($q) {
            $q->where('modality', 'in_person');
        });
    }

    /**
     * Scope para filtrar por ruta
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
        return $query->whereHas('training', function ($q) use ($cityId) {
            $q->where('city_id', $cityId);
        });
    }

    /**
     * Verificar si existe un soporte para una capacitación
     */
    public static function existsForTraining(int $trainingId): bool
    {
        return static::where('training_id', $trainingId)->exists();
    }
}
