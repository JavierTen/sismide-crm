<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Método requerido por FilamentUser
    public function canAccessPanel(Panel $panel): bool
    {
        // Permitir acceso si tiene cualquier rol activo
        return $this->roles()->count() > 0;
    }

    // Método helper para verificar si puede gestionar usuarios
    public function canManageUsers(): bool
    {
        return $this->hasAnyPermission([
            'listUsers',
            'createUser',
            'editUser',
            'deleteUser'
        ]);
    }

    // Método helper para verificar si solo puede ver usuarios
    public function canOnlyViewUsers(): bool
    {
        return $this->can('listUsers') &&
            !$this->can('createUser') &&
            !$this->can('editUser') &&
            !$this->can('deleteUser');
    }
}
