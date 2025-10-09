<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FairEvaluation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // Datos del Emprendimiento
        'entrepreneur_id',

        // Feria
        'fair_id',

        // Fecha de participación
        'participation_date',
        'participation_photo_path',

        // Experiencia en la Feria
        'organization_rating',
        'visitor_flow',
        'generated_contacts',
        'strategic_contacts_details',

        // Impacto
        'product_visibility',
        'total_sales',
        'order_value',
        'sufficient_products',
        'established_productive_chain',
        'productive_chain_details',
        'observations',

        // Manager
        'manager_id',
    ];

    protected static function boot()
    {
        parent::boot();

        // Al actualizar: eliminar foto antigua si se cambió
        static::updating(function ($fairEvaluation) {
            if ($fairEvaluation->isDirty('participation_photo_path')) {
                $oldPhoto = $fairEvaluation->getOriginal('participation_photo_path');

                if ($oldPhoto && Storage::disk('public')->exists($oldPhoto)) {
                    Storage::disk('public')->delete($oldPhoto);
                }
            }
        });

        // Al eliminar (soft delete): NO eliminar la foto aún
        static::deleting(function ($fairEvaluation) {
            // No hacer nada en soft delete
        });

        // Al forzar eliminación: eliminar la foto permanentemente
        static::forceDeleting(function ($fairEvaluation) {
            if ($fairEvaluation->participation_photo_path && Storage::disk('public')->exists($fairEvaluation->participation_photo_path)) {
                Storage::disk('public')->delete($fairEvaluation->participation_photo_path);
            }
        });

        // Al restaurar: no hacer nada (la foto ya existe)
        static::restoring(function ($fairEvaluation) {
            // La foto se mantiene
        });
    }

    protected $casts = [
        'participation_date' => 'date',
        'generated_contacts' => 'boolean',
        'sufficient_products' => 'boolean',
        'established_productive_chain' => 'boolean',
        'order_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function entrepreneur(): BelongsTo
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    public function fair(): BelongsTo
    {
        return $this->belongsTo(Fair::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Constantes para los enums
    public const ORGANIZATION_RATING_OPTIONS = [
        'excellent' => 'Excelente',
        'good' => 'Buena',
        'regular' => 'Regular',
        'poor' => 'Deficiente',
    ];

    public const VISITOR_FLOW_OPTIONS = [
        'very_high' => 'Muy alto',
        'adequate' => 'Adecuado',
        'low' => 'Bajo',
    ];

    public const PRODUCT_VISIBILITY_OPTIONS = [
        'yes' => 'Sí',
        'partially' => 'Parcialmente',
        'no' => 'No',
    ];

    public const TOTAL_SALES_OPTIONS = [
        'less_than_100k' => 'Menos de $100.000',
        'between_100k_200k' => 'Entre $100.000 - $200.000',
        'between_200k_500k' => 'Entre $200.000 - $500.000',
        'between_500k_1m' => 'Entre $500.000 - $1.000.000',
        'more_than_1m' => 'Más de $1.000.000',
    ];

    // Scopes
    public function scopeByManager($query, $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeByFair($query, $fairId)
    {
        return $query->where('fair_id', $fairId);
    }

    public function scopeByEntrepreneur($query, $entrepreneurId)
    {
        return $query->where('entrepreneur_id', $entrepreneurId);
    }

    public function scopeWithGeneratedContacts($query)
    {
        return $query->where('generated_contacts', true);
    }

    public function scopeWithProductiveChain($query)
    {
        return $query->where('established_productive_chain', true);
    }

    // Accessors
    public function getOrganizationRatingNameAttribute(): string
    {
        return self::ORGANIZATION_RATING_OPTIONS[$this->organization_rating] ?? $this->organization_rating;
    }

    public function getVisitorFlowNameAttribute(): string
    {
        return self::VISITOR_FLOW_OPTIONS[$this->visitor_flow] ?? $this->visitor_flow;
    }

    public function getProductVisibilityNameAttribute(): string
    {
        return self::PRODUCT_VISIBILITY_OPTIONS[$this->product_visibility] ?? $this->product_visibility;
    }

    public function getTotalSalesNameAttribute(): string
    {
        return self::TOTAL_SALES_OPTIONS[$this->total_sales] ?? $this->total_sales;
    }

    public function getFormattedOrderValueAttribute(): string
    {
        return '$' . number_format($this->order_value, 0, ',', '.');
    }
}
