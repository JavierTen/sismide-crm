<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Pqrf extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pqrfs';

    protected $fillable = [
        'entrepreneur_id',
        'manager_id',
        'type',
        'description',
        'incident_date',
        'city_id',
        'evidence_files',
        'status',
        'response',
        'response_date',
        'response_files',
        'responded_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'response_date' => 'date',
        'evidence_files' => 'array',
        'response_files' => 'array',
    ];

    /**
     * Boot the model and register events
     */
    protected static function boot()
    {
        parent::boot();

        // Evento antes de actualizar
        static::updating(function ($pqrf) {
            $original = $pqrf->getOriginal();

            // Eliminar archivos antiguos de evidencia si se actualizan
            if ($pqrf->isDirty('evidence_files') && !empty($original['evidence_files'])) {
                static::deleteFiles($original['evidence_files']);
            }

            // Eliminar archivos antiguos de respuesta si se actualizan
            if ($pqrf->isDirty('response_files') && !empty($original['response_files'])) {
                static::deleteFiles($original['response_files']);
            }
        });

        // Evento al eliminar (soft delete)
        static::deleted(function ($pqrf) {
            static::deleteAllFiles($pqrf);
        });

        // Evento al eliminar permanentemente
        static::forceDeleted(function ($pqrf) {
            static::deleteAllFiles($pqrf);
        });
    }

    /**
     * Eliminar todos los archivos del modelo
     */
    private static function deleteAllFiles($pqrf)
    {
        if (!empty($pqrf->evidence_files)) {
            static::deleteFiles($pqrf->evidence_files);
        }

        if (!empty($pqrf->response_files)) {
            static::deleteFiles($pqrf->response_files);
        }
    }

    /**
     * Eliminar archivos del storage
     */
    private static function deleteFiles($files)
    {
        if (is_string($files)) {
            $files = json_decode($files, true) ?? [$files];
        }

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!empty($file) && Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }
        }
    }

    // Relaciones
    public function entrepreneur()
    {
        return $this->belongsTo(Entrepreneur::class, 'entrepreneur_id')->withTrashed();
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInReview($query)
    {
        return $query->where('status', 'in_review');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Métodos auxiliares
    public static function typeOptions(): array
    {
        return [
            'petition' => 'Petición',
            'complaint' => 'Queja',
            'claim' => 'Reclamo',
            'congratulation' => 'Felicitación',
            'suggestion' => 'Sugerencia',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'Pendiente',
            'in_review' => 'En Revisión',
            'closed' => 'Cerrada',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }

    public function getStatusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'in_review' => 'info',
            'closed' => 'success',
            default => 'gray',
        };
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInReview(): bool
    {
        return $this->status === 'in_review';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function hasResponse(): bool
    {
        return !empty($this->response);
    }
}
