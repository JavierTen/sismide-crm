<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'visits';

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
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'string', // guarda en DB como time; úsalo como string o Carbon según necesidad
        'strengthened' => 'boolean',
        'rescheduled'  => 'boolean',
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

    public function originalVisit()
    {
        return $this->belongsTo(self::class, 'original_visit_id');
    }

    public function reschedules()
    {
        return $this->hasMany(self::class, 'original_visit_id');
    }
}
