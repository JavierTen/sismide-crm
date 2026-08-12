<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'training_id',
        'city_id',
        'session_date',
        'manager_id',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (TrainingSession $session) {
            $session->training?->syncStatus();
        });
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
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

    public function getAttendedCountAttribute(): int
    {
        return $this->participations()->where('attended', true)->count();
    }

    public function getTotalCountAttribute(): int
    {
        return $this->participations()->count();
    }
}
