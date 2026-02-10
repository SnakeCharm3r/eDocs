<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clearance_work_flow extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'requested_resource_id',
        'work_flow_status',
        'work_flow_completed'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function histories()
    {
        return $this->hasMany(Clearance_work_flow_history::class, 'work_flow_id');
    }

    public function clearanceForm()
    {
        return $this->belongsTo(ClearanceForm::class, 'requested_resource_id');
    }
}
