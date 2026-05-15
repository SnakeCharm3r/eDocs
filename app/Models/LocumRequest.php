<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocumRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'locum_agreement_id',
        'rate_used',               // NEW: Preserves the rate used when request was created (for rejected claims)
        'locum_month',
        'locum_year',
        'hours_per_locum',
        'number_of_days',          // (legacy; still ok to keep)
        'total_hours',             // (legacy; sum of eligible day-hours—can keep for reference)
        'grand_total_locums',      // NEW
        'total_amount',            // NEW (pre-tax/calc)
        'total_amount_payable',    // still kept if you have deductions elsewhere
        'per_shift_breakdown',     // NEW JSON
        'worked_days',             // JSON of daily rows as submitted
        'reason',
        'description',
        'submited_by_incharge',
        'approval_flow',
        'unit_ids',
        'platform_ids',
        'incharge_user_ids',
        'platform_manager_ids',

    ];

    protected $casts = [
        'total_amount'          => 'decimal:2',
        'total_amount_payable'  => 'decimal:2',
        'total_hours'           => 'decimal:2',
        'rate_used'             => 'decimal:2',
        'per_shift_breakdown'   => 'array',
        'unit_ids' => 'array',
        'platform_ids' => 'array',
        'incharge_user_ids' => 'array',
        'platform_manager_ids' => 'array',
        'worked_days'           => 'array',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function agreement()
    {
        return $this->belongsTo(LocumAgreement::class);
    }
    public function locumAgreement()
    {
        return $this->belongsTo(LocumAgreement::class, 'locum_agreement_id');
    }
    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'locum_request_id');
    }
    public function department()
    {
        return $this->belongsTo(Departments::class, 'deptid');
    }
    public function submittedByIncharge()
    {
        return $this->belongsTo(User::class, 'submited_by_incharge');
    }
    public function incharge()
    {
        return $this->belongsTo(User::class, 'incharge_user_id');
    }
}
