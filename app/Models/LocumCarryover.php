<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocumCarryover extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'year',
        'month',
        'hours_per_locum',
        'hours'
    ];

    protected $casts = [
        'hours' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function shift()
    {
        return $this->belongsTo(ShiftSetting::class, 'shift_id');
    }
}
