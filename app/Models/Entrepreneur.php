<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use App\Models\Visit;

class Entrepreneur extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $table = 'entrepreneurs';
    protected $guard = 'entrepreneur';

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
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'status' => 'boolean',
        'birth_date' => 'date',
        'admission_date' => 'date',
        'password' => 'hashed',
    ];

    /**
     * Método REQUERIDO por HasName interface
     * Este es el que Filament usa internamente
     */
    public function getFilamentName(): string
    {
        return $this->full_name ?? $this->email ?? 'Emprendedor';
    }

    /**
     * Verificar acceso al panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->status;
    }

    /**
     * OPCIONAL: Avatar personalizado
     */
    public function getFilamentAvatarUrl(): ?string
    {
        $name = $this->getFilamentName();
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=7F9CF5&background=EBF4FF';
    }

    // ... resto de relaciones

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

    public function visits()
    {
        return $this->hasMany(\App\Models\Visit::class);
    }

    public function pqrfs()
    {
        return $this->hasMany(Pqrf::class, 'entrepreneur_id');
    }

    public function characterizations()
    {
        return $this->hasMany(\App\Models\Characterization::class);
    }

    public function businessDiagnoses()
    {
        return $this->hasMany(\App\Models\BusinessDiagnosis::class);
    }

    public function businessDiagnosis()
    {
        return $this->hasOne(BusinessDiagnosis::class);
    }
}
