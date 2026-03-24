<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnCallRate extends Model
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
     * Get active rates ordered by education level for a specific date.
     * Returns rates where the date falls between start_date and end_date.
     */
    public static function activeOrdered(?\Carbon\Carbon $date = null)
    {
        $date = $date ?? now();
        return static::where('is_active', true)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->orderBy('education_level')
            ->get();
    }

    /**
     * Users that have this rate assigned
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_oncall_rate', 'on_call_rate_id', 'user_id')
            ->withTimestamps();
    }
}
