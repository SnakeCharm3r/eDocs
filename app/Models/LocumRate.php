<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocumRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'education_level',
        'start_date',
        'end_date',
        'rate',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'rate' => 'integer',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get all rates marked Active in locum rates settings (is_active = true), ordered by education level.
     * Use for dropdowns on locum agreements so users always see active rates from settings.
     */
    public static function activeRatesOrdered()
    {
        return static::where('is_active', true)
            ->orderBy('education_level')
            ->get();
    }

    /**
     * Get active rates ordered by education level for a given date.
     * Returns rates where the date falls between start_date and end_date.
     */
    public static function activeOrdered($date = null)
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $dateStr = $date->format('Y-m-d');
        return static::where('is_active', true)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->orderBy('education_level')
            ->get();
    }

    /**
     * Get the active rate for a given education level on a given date (for agreement/claim validation).
     */
    public static function getRateForLevel(string $educationLevel, $date = null): ?self
    {
        $date = $date ? \Carbon\Carbon::parse($date) : now();
        $dateStr = $date->format('Y-m-d');
        return static::where('education_level', $educationLevel)
            ->where('is_active', true)
            ->where('start_date', '<=', $dateStr)
            ->where('end_date', '>=', $dateStr)
            ->first();
    }
}


