<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'from_department_id',
        'from_division_id',
        'from_location_id',
        'from_custom_location',
        'to_department_id',
        'to_division_id',
        'to_location_id',
        'to_custom_location',
        'from_user_id',
        'to_user_id',
        'movement_date',
        'moved_by',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
    ];

    /**
     * Get the asset that was moved
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * Get the source department
     */
    public function fromDepartment()
    {
        return $this->belongsTo(Departments::class, 'from_department_id');
    }

    /**
     * Get the destination department
     */
    public function toDepartment()
    {
        return $this->belongsTo(Departments::class, 'to_department_id');
    }

    /**
     * Get the source division
     */
    public function fromDivision()
    {
        return $this->belongsTo(Division::class, 'from_division_id');
    }

    /**
     * Get the destination division
     */
    public function toDivision()
    {
        return $this->belongsTo(Division::class, 'to_division_id');
    }

    /**
     * Get the source location
     */
    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * Get the destination location
     */
    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * Get the user who moved the asset
     */
    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }

    /**
     * Get the source user
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the destination user
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
