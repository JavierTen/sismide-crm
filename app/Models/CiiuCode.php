<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CiiuCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'descripcion',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    // Scopes para consultas comunes
    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('code', 'asc');
    }

    public function getCodeDescripcionCombinedAttribute(): string
    {
        return "{$this->code} - {$this->descripcion}";
    }

    public function entrepreneurs()
    {
        return $this->hasMany(Entrepreneur::class);
    }
}
