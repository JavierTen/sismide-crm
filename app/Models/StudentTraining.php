<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use App\Traits\TracksUpdatedBy;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentTraining extends Model
{
    use SoftDeletes, TracksUpdatedBy, LogsModelActivity;

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));
    }

    protected $fillable = [
        'name',
        'objective',
        'manager_id',
        'updated_by_id',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function facilitators(): HasMany
    {
        return $this->hasMany(StudentTrainingFacilitator::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(StudentTrainingSession::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(set: fn ($value) => mb_strtoupper($value));
    }

    public const METHODOLOGY_OPTIONS = [
        'taller_practico'  => 'Taller práctico',
        'charla_magistral' => 'Charla magistral',
        'dinamica_grupal'  => 'Dinámica grupal',
        'estudio_caso'     => 'Estudio de caso',
        'bootcamp'         => 'Bootcamp',
        'otro'             => 'Otro',
    ];

    public const RESULT_RATING_OPTIONS = [
        'excelente'  => 'Excelente',
        'buena'      => 'Buena',
        'regular'    => 'Regular',
        'deficiente' => 'Deficiente',
    ];
}
