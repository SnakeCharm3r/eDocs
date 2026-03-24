<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalKpi extends Model
{
    use HasFactory;

    protected $table = 'goal_kpis';

    protected $fillable = [
        'goal_id',
        'name',
        'target',
        'unit',
        'weight',
        'baseline',
        'due_date',
        'createdBy',
        'updatedBy',
        'delete_status',
    ];

    protected $casts = [
        'due_date' => 'date',
        'weight' => 'decimal:2',
    ];

    public function goal()
    {
        return $this->belongsTo(Goal::class, 'goal_id');
    }
}
