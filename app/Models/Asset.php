<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'asset_code',
        'category_id',
        'brand',
        'model',
        'specifications',
        'status',
        'purchase_date',
        'warranty_expiry',
        'department_id',
        'division_id',
        'location_id',
        'assigned_to_user_id',
        'line_manager_id',
        'custom_location',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
    ];

    /**
     * Get the category that owns the asset
     */
    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    /**
     * Get the department that owns the asset
     */
    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    /**
     * Get the division/entity that owns the asset
     */
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /**
     * Get the location/branch of the asset
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Get the user assigned to this asset
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get the line manager of the department
     */
    public function lineManager()
    {
        return $this->belongsTo(User::class, 'line_manager_id');
    }

    /**
     * Get all movements for this asset
     */
    public function movements()
    {
        return $this->hasMany(AssetMovement::class, 'asset_id');
    }

    /**
     * Get all maintenance records for this asset
     */
    public function maintenance()
    {
        return $this->hasMany(AssetMaintenance::class, 'asset_id');
    }

    /**
     * Get retirement record if exists
     */
    public function retirement()
    {
        return $this->hasOne(AssetRetirement::class, 'asset_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by division
     */
    public function scopeByDivision($query, $divisionId)
    {
        return $query->where('division_id', $divisionId);
    }

    /**
     * Scope to filter by location
     */
    public function scopeByLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
