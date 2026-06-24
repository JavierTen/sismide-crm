<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationalInstitution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'city_id',
        'name',
        'campus',
        'principal_name',
        'proposed_teacher',
        'phone',
        'email',
        'manager_id',
    ];

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->name} - {$this->campus}", ' -');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(InstitutionEvaluation::class);
    }
}
