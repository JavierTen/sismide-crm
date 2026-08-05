<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        'name',
        'status',
        'updated_by_id',
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
        return $query->where('status', true)->orderBy('name', 'asc');
    }

    public function entrepreneurs()
    {
        return $this->hasMany(Entrepreneur::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }
}
