<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityLocation extends Model
{
    use HasFactory;
    protected $table = 'Facility_Locations';

    // Mass assignable fields
    protected $fillable = [
        'name',
        'code',
        'location',
        'city',
        'region',
    ];

    /**
     * Get all assets for this hospital location.
     */
    public function assets()
    {
        return $this->hasMany(FacilityAsset::class);
    }
}
