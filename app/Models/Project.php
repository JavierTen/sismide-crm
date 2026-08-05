<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        'name',
        'status',
        'detail',
        'acronym',
        'type',
        'participant_goal',
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
        return $query->where('status', true);
    }

    public function entrepreneurs()
    {
        return $this->hasMany(Entrepreneur::class);
    }

}
