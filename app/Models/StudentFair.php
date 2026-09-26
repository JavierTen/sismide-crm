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

    /** ¿Con qué tipo de actor se articuló? (selección múltiple) */
    public const ARTICULATION_ACTOR_TYPE_OPTIONS = [
        'empresa_privada'       => 'Empresa privada',
        'entidad_publica'       => 'Entidad pública',
        'institucion_educativa' => 'Otra institución educativa',
        'ong'                   => 'ONG',
        'otro_emprendimiento'   => 'Otro emprendimiento',
        'ninguno'               => 'Ninguno',
    ];

    /** ¿Cuántas articulaciones concretas se generaron? */
    public const ARTICULATIONS_COUNT_OPTIONS = [
        'ninguna' => 'Ninguna',
        '1_2'     => '1 a 2',
        '3_5'     => '3 a 5',
        'mas_5'   => 'Más de 5',
    ];

    /** ¿En qué eslabón de la cadena se identificó la oportunidad? */
    public const CHAIN_LINK_OPTIONS = [
        'proveeduria'      => 'Proveeduría de insumos',
        'produccion'       => 'Producción',
        'transformacion'   => 'Transformación',
        'comercializacion' => 'Comercialización',
        'distribucion'     => 'Distribución',
        'otro'             => 'Otro',
    ];

    /** ¿Con qué tipo de actor se generó el encadenamiento? (selección múltiple) */
    public const CHAIN_ACTOR_TYPE_OPTIONS = [
        'proveedor'           => 'Proveedor',
        'cliente'             => 'Cliente',
        'aliado_comercial'    => 'Aliado comercial',
        'distribuidor'        => 'Distribuidor',
        'otro_emprendimiento' => 'Otro emprendimiento',
        'ninguno'             => 'Ninguno',
    ];

    public const SALES_RANGE_OPTIONS = [
        'sin_ventas' => 'Sin ventas',
        'lt50k'      => 'Menos de $50.000',
        '50k_200k'   => '$50.000 a $200.000',
        '200k_500k'  => '$200.000 a $500.000',
        'gt500k'     => 'Más de $500.000',
    ];

    /** ¿El balance de la feria fue positivo para el emprendimiento? */
    public const SALES_BALANCE_OPTIONS = [
        'positivo_ganancia' => 'Sí, cubrió costos y generó ganancia',
        'positivo_costos'   => 'Sí, cubrió costos únicamente',
        'negativo'          => 'No, no cubrió costos',
        'no_aplica'         => 'No aplica',
    ];

    public const ORGANIZATION_RATING_OPTIONS = [
        'excelente' => 'Excelente',
        'buena'     => 'Buena',
        'regular'   => 'Regular',
        'deficiente'=> 'Deficiente',
    ];

    public const VISITOR_FLOW_OPTIONS = [
        'muy_alto' => 'Muy alto',
        'adecuado' => 'Adecuado',
        'bajo'     => 'Bajo',
    ];
}
