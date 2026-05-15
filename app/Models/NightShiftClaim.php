<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NightShiftClaim extends Model
{
    protected $fillable = [
        'department_id',
        'submitted_by',
        'month',
        'year',
        'type_of_allowance',
        'number_of_employees',
        'total_days_worked',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function employees()
    {
        return $this->hasMany(NightShiftClaimEmployee::class);
    }

    public function workflow()
    {
        return $this->hasOne(\App\Models\Workflow::class, 'night_shift_claim_id');
    }

    public function recalculate(): void
    {
        $this->number_of_employees = $this->employees()->count();
        $this->total_days_worked   = $this->employees()->sum('days_on_duty');
        $this->save();
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            default    => 'badge bg-warning text-dark',
        };
    }
}
