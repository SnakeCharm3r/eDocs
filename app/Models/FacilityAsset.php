<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityAsset extends Model
{
    use HasFactory;

    protected $table = 'facility_assets';

    protected $fillable = [
        'name',
        'type',
        'description',
        'model',
        'manufacturer',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'vendor',
        'vendor_contact',
        'facility_location_id',
        'status',
    ];

    /**
     * Get the facility location that owns the asset.
     */
    public function facilityLocation()
    {
        return $this->belongsTo(FacilityLocation::class);
    }
}
