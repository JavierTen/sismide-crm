<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Fair extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Información General
        'name',
        'location',
        'address',
        'start_date',
        'end_date',

         // Georreferenciación
         'latitude',
         'longitude',

        // Organización
        'organizer_name',
        'organizer_position',
        'organizer_phone',
        'organizer_email',

        // Observaciones
        'observations',

        // Manager
        'manager_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Accessors para convertir a mayúsculas automáticamente
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => strtoupper($value),
        );
    }

    protected function location(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => strtoupper($value),
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => strtoupper($value),
        );
    }

    // Scopes
    public function scopeByManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeActive($query)
    {
        return $query->whereDate('end_date', '>=', now());
    }

    public function scopeFinished($query)
    {
        return $query->whereDate('end_date', '<', now());
    }

    public function scopeInProgress($query)
    {
        return $query->whereDate('start_date', '<=', now())
                    ->whereDate('end_date', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('start_date', '>', now());
    }

    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
                    ->whereNotNull('longitude');
    }

    // Accessors calculados
    public function getStatusAttribute(): string
    {
        $now = now();

        if ($this->start_date > $now) {
            return 'upcoming'; // Próxima
        }

        if ($this->end_date < $now) {
            return 'finished'; // Finalizada
        }

        return 'in_progress'; // En curso
    }

    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->end_date >= now();
    }
}
