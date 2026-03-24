<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalAlignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'aligned_goal_id',
        'createdBy',
        'updatedBy',
        'delete_status',
    ];

    public function goal()
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }

    public function alignedGoal()
    {
        return $this->belongsTo(Goal::class, 'aligned_goal_id');
    }
}
