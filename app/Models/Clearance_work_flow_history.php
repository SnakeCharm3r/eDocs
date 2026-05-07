<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Clearance_work_flow_history extends Model
{
    use HasFactory;

    protected $fillable =[
        'work_flow_id',
        'forwarded_by',
        'attended_by',
        'attend_date',
        'status',
        'remark',
        'parent_id',
        'step_name',
        'who_approve'
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'who_approve');
    }

    public function forwardedBy()
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }

    public function attendedBy()
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    public function workflow()
    {
        return $this->belongsTo(Clearance_work_flow::class, 'work_flow_id');
    }
}
