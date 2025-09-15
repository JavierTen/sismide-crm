<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entrepreneur extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'entrepreneurs';

    protected $fillable = [
        'status',
        'document_type_id',
        'document_number',
        'full_name',
        'gender_id',
        'marital_status_id',
        'birth_date',
        'phone',
        'address',
        'email',
        'city_id',
        'education_level_id',
        'population_id',
        'state_id',
        'manager_id',
        'project_id',
        'service',
        'admission_date',
        'cohort_id',
        'user_id',
        'traffic_light',
    ];

    protected $casts = [
        'status' => 'boolean',
        'birth_date' => 'date',
        'admission_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class);
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function population()
    {
        return $this->belongsTo(Population::class);
    }

    public function entrepreneurshipStage()
    {
        return $this->belongsTo(EntrepreneurshipStage::class);
    }

    public function economicActivity()
    {
        return $this->belongsTo(EconomicActivity::class);
    }

    public function productiveLine()
    {
        return $this->belongsTo(ProductiveLine::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function business()
    {
        return $this->hasOne(Business::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
