<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightShiftClaimEmployee extends Model
{
    protected $fillable = [
        'night_shift_claim_id',
        'user_id',
        'emp_code',
        'employee_name',
        'days_on_duty',
        'date',
        'platform_id',
        'unit_id',
        'hours',
    ];

    public function platform()
    {
        return $this->belongsTo(\App\Models\Platform::class);
    }

    public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class);
    }

    public function claim()
    {
        return $this->belongsTo(NightShiftClaim::class, 'night_shift_claim_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
