<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'educational_institution_id',
        'document_type_id',
        'document_number',
        'name',
        'age',
        'gender_id',
        'grade',
        'course',
        'phone',
        'email',
        'guardian_name',
        'guardian_phone',
        'manager_id',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    public static function gradeOptions(): array
    {
        return [
            '10' => '10°',
            '11' => '11°',
        ];
    }

    public function educationalInstitution(): BelongsTo
    {
        return $this->belongsTo(EducationalInstitution::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function characterizations(): HasMany
    {
        return $this->hasMany(StudentCharacterization::class);
    }
}
