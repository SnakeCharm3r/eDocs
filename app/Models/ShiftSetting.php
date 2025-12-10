<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSetting extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_official_duty',
        'start_time',
        'end_time',
        'days_per_week',
        'hours_per_day',
        'weekly_hours',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_official_duty' => 'boolean',
        'is_active'        => 'boolean',
        'start_time'       => 'datetime:H:i',
        'end_time'         => 'datetime:H:i',
        'days_per_week'    => 'integer',
        'hours_per_day'    => 'decimal:2',
        'weekly_hours'     => 'decimal:2',
    ];

    // If weekly_hours is empty, compute on the fly
    public function getComputedWeeklyHoursAttribute(): ?float
    {
        $days = $this->days_per_week ?: 0;
        $hpd  = $this->hours_per_day ?: 0.0;

        if ($days > 0 && $hpd > 0) {
            return round($days * $hpd, 2);
        }
        return null;
    }
}
