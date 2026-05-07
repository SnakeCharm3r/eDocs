<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetRetirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'retirement_date',
        'reason',
        'notes',
        'retired_by',
    ];

    protected $casts = [
        'retirement_date' => 'date',
    ];

    /**
     * Get the asset that was retired
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * Get the user who retired the asset
     */
    public function retiredBy()
    {
        return $this->belongsTo(User::class, 'retired_by');
    }
}
