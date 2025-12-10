<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'maintenance_type',
        'description',
        'date_performed',
        'performed_by',
        'performed_by_user_id',
        'cost',
        'notes',
    ];

    protected $casts = [
        'date_performed' => 'date',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the asset that was maintained
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * Get the user who performed the maintenance
     */
    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }

    /**
     * Scope to filter by maintenance type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }
}
