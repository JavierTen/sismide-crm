<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Un inicio de sesión y, si lo hubo, su cierre.
 *
 * Laravel solo avisa del cierre cuando el usuario pulsa "Cerrar sesión". Si la
 * sesión expira por inactividad o se cierra el navegador no hay ningún evento,
 * así que esos casos se deducen al leer: sin cierre registrado y con la última
 * actividad más vieja que la duración de la sesión, se consideran expirados.
 */
class LoginLog extends Model
{
    public const STATUS_ACTIVE  = 'active';
    public const STATUS_MANUAL  = 'manual';
    public const STATUS_FORCED  = 'forced';
    public const STATUS_EXPIRED = 'expired';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE  => 'Activa',
        self::STATUS_MANUAL  => 'Cerrada por el usuario',
        self::STATUS_FORCED  => 'Cierre forzado',
        self::STATUS_EXPIRED => 'Expirada por inactividad',
    ];

    public const PANEL_LABELS = [
        'dashboard'   => 'Dashboard',
        'eje'         => 'EJE',
        'emprendedor' => 'Emprendedor',
    ];

    /** Rol que se asigna a quien entra por el panel Emprendedor. */
    public const ENTREPRENEUR_ROLE = 'Emprendedor';

    /** Clave de sesión donde se guarda el registro activo de cada guard. */
    public const SESSION_KEY = 'login_log_ids';

    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'user_name',
        'user_email',
        'user_roles',
        'panel',
        'guard',
        'via_remember',
        'ip_address',
        'user_agent',
        'login_at',
        'last_activity_at',
        'logout_at',
        'logout_reason',
    ];

    protected $casts = [
        'user_roles'       => 'array',
        'via_remember'     => 'boolean',
        'login_at'         => 'datetime',
        'last_activity_at' => 'datetime',
        'logout_at'        => 'datetime',
    ];

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Minutos sin actividad tras los que Laravel da la sesión por vencida. */
    public static function sessionLifetime(): int
    {
        return (int) config('session.lifetime', 120);
    }

    public function getStatusAttribute(): string
    {
        if ($this->logout_at) {
            return $this->logout_reason === self::STATUS_FORCED
                ? self::STATUS_FORCED
                : self::STATUS_MANUAL;
        }

        $lastSeen = $this->last_activity_at ?? $this->login_at;

        return $lastSeen->lt(now()->subMinutes(self::sessionLifetime()))
            ? self::STATUS_EXPIRED
            : self::STATUS_ACTIVE;
    }

    /**
     * Momento en que terminó la sesión. Para las expiradas se toma la última
     * actividad conocida: el vencimiento real no se registra.
     */
    public function getEndedAtAttribute(): ?Carbon
    {
        return match ($this->status) {
            self::STATUS_ACTIVE  => null,
            self::STATUS_EXPIRED => $this->last_activity_at ?? $this->login_at,
            default              => $this->logout_at,
        };
    }

    /** Duración en minutos; las activas cuentan hasta ahora. */
    public function getDurationMinutesAttribute(): int
    {
        $end = $this->ended_at ?? now();

        return (int) max(0, $this->login_at->diffInMinutes($end));
    }

    public function getDurationLabelAttribute(): string
    {
        $minutes = $this->duration_minutes;

        if ($minutes < 1) {
            return 'Menos de 1 min';
        }

        $hours = intdiv($minutes, 60);
        $rest  = $minutes % 60;

        return $hours > 0 ? "{$hours} h {$rest} min" : "{$rest} min";
    }

    public function getRolesLabelAttribute(): string
    {
        return implode(', ', $this->user_roles ?? []) ?: 'Sin rol';
    }

    public function getUserTypeLabelAttribute(): string
    {
        return match ($this->authenticatable_type) {
            Entrepreneur::class => 'Emprendedor',
            User::class         => 'Usuario',
            default             => '—',
        };
    }

    /**
     * Filtra por estado en SQL, replicando la lógica de getStatusAttribute().
     */
    public function scopeWhereStatus(Builder $query, string $status): Builder
    {
        $threshold = now()->subMinutes(self::sessionLifetime());

        return match ($status) {
            self::STATUS_MANUAL  => $query->whereNotNull('logout_at')
                ->where(fn (Builder $q) => $q->whereNull('logout_reason')->orWhere('logout_reason', '!=', self::STATUS_FORCED)),
            self::STATUS_FORCED  => $query->whereNotNull('logout_at')->where('logout_reason', self::STATUS_FORCED),
            self::STATUS_EXPIRED => $query->whereNull('logout_at')
                ->whereRaw('COALESCE(last_activity_at, login_at) < ?', [$threshold]),
            self::STATUS_ACTIVE  => $query->whereNull('logout_at')
                ->whereRaw('COALESCE(last_activity_at, login_at) >= ?', [$threshold]),
            default              => $query,
        };
    }
}
