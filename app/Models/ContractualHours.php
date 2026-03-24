<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractualHours extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'working_days',
        'public_holidays',
        'contractual_hours',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'working_days' => 'integer',
        'contractual_hours' => 'integer',
        'public_holidays' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getMonthNameAttribute()
    {
        return \Carbon\Carbon::create($this->year, $this->month, 1)->format('F');
    }

    public static function getForMonth($year, $month)
    {
        return self::where('year', $year)
            ->where('month', $month)
            ->first();
    }
}
