<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductiveLine extends Model
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


    public function scopeActive($query)
    {
        return $query->where('status', true)->orderBy('created_at', 'asc');
    }

    public function entrepreneurs()
    {
        return $this->hasMany(Entrepreneur::class);
    }
}
