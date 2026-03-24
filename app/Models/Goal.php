<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_cycle_id',
        'parent_goal_id',
        'level',
        'title',
        'description',
        'department_id',
        'unit_id',
        'owner_user_id',
        'status',
        'submitted_to_line_manager_at',
        'line_manager_approved_by',
        'line_manager_approved_at',
        'submitted_to_hec_at',
        'hec_approved_by',
        'hec_approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'submitted_to_hrbp_at',
        'createdBy',
        'updatedBy',
        'delete_status',
    ];

    protected $casts = [
        'submitted_to_line_manager_at' => 'datetime',
        'line_manager_approved_at' => 'datetime',
        'submitted_to_hec_at' => 'datetime',
        'hec_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'submitted_to_hrbp_at' => 'datetime',
    ];

    public function cycle()
    {
        return $this->belongsTo(GoalCycle::class, 'goal_cycle_id');
    }

    public function parent()
    {
        return $this->belongsTo(Goal::class, 'parent_goal_id');
    }

    public function children()
    {
        return $this->hasMany(Goal::class, 'parent_goal_id');
    }

    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function kpis()
    {
        return $this->hasMany(GoalKpi::class, 'goal_id')->where('delete_status', 0);
    }

    public function alignments()
    {
        return $this->hasMany(GoalAlignment::class, 'goal_id')->where('delete_status', 0);
    }
}
