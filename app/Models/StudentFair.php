<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class StudentFair extends Model
{
    use SoftDeletes, TracksUpdatedBy;

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));
    }

    protected $fillable = [
        'name',
        'location',
        'address',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'organizer_name',
        'organizer_position',
        'organizer_phone',
        'organizer_email',
        'observations',
        'manager_id',
        'updated_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'latitude'   => 'decimal:8',
        'longitude'  => 'decimal:8',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(StudentFairParticipation::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(set: fn ($v) => mb_strtoupper($v));
    }

    protected function location(): Attribute
    {
        return Attribute::make(set: fn ($v) => mb_strtoupper($v));
    }

    protected function address(): Attribute
    {
        return Attribute::make(set: fn ($v) => $v ? mb_strtoupper($v) : null);
    }

    public const ARTICULATIONS_COUNT_OPTIONS = [
        '1'    => '1',
        '2-5'  => '2 – 5',
        '6-10' => '6 – 10',
        '>10'  => 'Más de 10',
    ];

    public const CHAIN_LINK_OPTIONS = [
        'proveedor'    => 'Proveedor',
        'cliente'      => 'Cliente',
        'distribuidor' => 'Distribuidor',
        'transformador'=> 'Transformador',
    ];

    public const SALES_RANGE_OPTIONS = [
        'sin_ventas' => 'Sin ventas',
        'lt50k'      => 'Menos de $50.000',
        '50k_200k'   => '$50.000 – $200.000',
        '200k_500k'  => '$200.000 – $500.000',
        'gt500k'     => 'Más de $500.000',
    ];

    public const SALES_BALANCE_OPTIONS = [
        'positivo' => 'Positivo',
        'neutro'   => 'Neutro',
        'negativo' => 'Negativo',
    ];

    public const ORGANIZATION_RATING_OPTIONS = [
        'excelente' => 'Excelente',
        'buena'     => 'Buena',
        'regular'   => 'Regular',
        'deficiente'=> 'Deficiente',
    ];

    public const VISITOR_FLOW_OPTIONS = [
        'alto'  => 'Alto',
        'medio' => 'Medio',
        'bajo'  => 'Bajo',
    ];
}
