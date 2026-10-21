<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuración guardada de la exportación por cláusulas.
 *
 * Pública: la ven todos los que tienen acceso al módulo. Privada: solo su
 * creador. En ambos casos solo el creador puede modificarla o eliminarla.
 */
class ExportTemplate extends Model
{
    use LogsModelActivity;

    public const VISIBILITY_PUBLIC  = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    public const VISIBILITY_LABELS = [
        self::VISIBILITY_PUBLIC  => 'Pública',
        self::VISIBILITY_PRIVATE => 'Privada',
    ];

    protected $fillable = ['user_id', 'name', 'visibility', 'config'];

    protected $casts = [
        'config' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Plantillas que puede usar un usuario: las suyas y las públicas. */
    public function scopeVisibleTo(Builder $query, Authenticatable $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('user_id', $user->getAuthIdentifier())
            ->orWhere('visibility', self::VISIBILITY_PUBLIC));
    }

    public function isOwnedBy(?Authenticatable $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->getAuthIdentifier();
    }
}
