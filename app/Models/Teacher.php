<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'educational_institution_id',
        'document_type_id',
        'document_number',
        'name',
        'area',
        'email',
        'phone',
        'status',
        'program_start_date',
        'notes',
        'manager_id',
    ];

    protected $casts = [
        'program_start_date' => 'date',
    ];

    public static function statusOptions(): array
    {
        return [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'withdrawn' => 'Retirado del programa',
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

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class);
    }
}
