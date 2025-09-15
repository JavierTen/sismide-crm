<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'department_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'department_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    // Scopes para consultas comunes
    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('name', 'asc');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function entrepreneurs()
    {
        return $this->hasMany(Entrepreneur::class);
    }

    public function wards()
    {
        return $this->hasMany(Ward::class);
    }
}
