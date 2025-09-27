<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'ward_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'ward_id' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('name', 'asc');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function city()
    {
        return $this->ward->city();
    }

    public function department()
    {
        return $this->ward->department();
    }
}
