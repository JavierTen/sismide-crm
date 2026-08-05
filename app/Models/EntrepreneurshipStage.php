<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntrepreneurshipStage extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy;

    protected $fillable = [
        'code',
        'name',
        'status',
        'updated_by_id',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true)->orderBy('created_at', 'asc');
    }

    public function getCodeNameCombinedAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    public function entrepreneurs()
    {
        return $this->hasMany(Entrepreneur::class);
    }
}
