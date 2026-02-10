<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractRenewal extends Model
{
    use HasFactory;
    protected $table = 'contract_renewals';

    protected $fillable = [
        'contract_id',
        'vendor_id',
        'vendor_option',
        'department_id',
        'division_id',
        'vendor_review',
        'service_requirements',
        'contract_type',
        'category',
        'cost',
        'duration_months',
        'status',
        'likelihood_rating',
        'impact_if_not_requested',
        'overall_risk',
        'created_by',
        'contract_file',
    ];

    // RELATIONSHIPS

    // Link to the original contract
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    // Link to the vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    // Link to the user who created the renewal
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function department(){
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function division(){
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function workflow(){
        return $this->hasMany(Workflow::class, 'contract_renewal_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper: check if within notice period
    public function isWithinNoticePeriod(): bool
    {
        $today = now();
        $noticeStart = $this->end_date->subMonths($this->notice_period_months);
        return $today >= $noticeStart && $today <= $this->end_date;
    }
}
