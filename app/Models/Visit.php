<?php

namespace App\Models;

use App\Scopes\YearColumnScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TracksUpdatedBy;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, SoftDeletes, TracksUpdatedBy;

    protected $table = 'visits';

    protected static function booted(): void
    {
        static::addGlobalScope(new YearColumnScope('created_at'));
    }

    protected $fillable = [
        'entrepreneur_id',
        'manager_id',
        'visit_date',
        'visit_time',
        'visit_type',
        'strengthened',
        'rescheduled',
        'original_visit_id',
        'reschedule_reason',
        'visit_result',
        'topics_and_commitment',
        'evidence_path',
        'updated_by_id',
        'confirmed_by_id',
        'confirmed_at',
    ];

    protected $casts = [
        'visit_date'    => 'date',
        'visit_time'    => 'string',
        'strengthened'  => 'boolean',
        'rescheduled'   => 'boolean',
        'evidence_path' => 'array',
        'confirmed_at'  => 'datetime',
    ];

    public function entrepreneur()
    {
        return $this->belongsTo(Entrepreneur::class, 'entrepreneur_id')->withTrashed();
    }

    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_id');
    }

    public function scopeActive($query)
    {
        return $query->where('deleted_at', null)->orderBy('created_at', 'asc');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'confirmed_by_id');
    }

    public function originalVisit()
    {
        return $this->belongsTo(self::class, 'original_visit_id');
    }

    public function reschedules()
    {
        return $this->hasMany(self::class, 'original_visit_id');
    }
}
