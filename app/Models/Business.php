<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entrepreneur_id',
        'business_name',
        'description',
        'creation_date',
        'status',
        'phone',
        'email',
        'address',
        'department_id',
        'city_id',
        'ward_id',
        'village_id',
        'georeferencing',
        'ciiu_code_id',
        'entrepreneurship_stage_id',
        'productive_line_id',
        'economic_activity_id',
        'business_zone',
        'influence_zone',
        'is_characterized',
        'aid_compliance',
    ];

    protected $casts = [
        'creation_date' => 'date',
        'deleted_at' => 'datetime',
        'village_id' => 'integer',
    ];

    public function entrepreneur()
    {
        return $this->belongsTo(Entrepreneur::class);
    }

    /**
     * Get the department where the business is located.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the municipality where the business is located.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the ward where the business is located.
     */
    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * Get the CIIU code associated with the business.
     */
    public function ciiuCode()
    {
        return $this->belongsTo(CiiuCode::class);
    }

    /**
     * Get the entrepreneurship stage of the business.
     */
    public function entrepreneurshipStage()
    {
        return $this->belongsTo(EntrepreneurshipStage::class);
    }

    /**
     * Get the productive line of the business.
     */
    public function productiveLine()
    {
        return $this->belongsTo(ProductiveLine::class);
    }

    /**
     * Scope a query to only include active businesses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function economicActivity()
    {
        return $this->belongsTo(EconomicActivity::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }


}
