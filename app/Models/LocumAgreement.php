<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocumAgreement extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'has_contract',
        'education_level',
        'locum_rate',
        'start_date',
        'end_date',
        'status',
        'rejection_status',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'locum_agreement_id');
    }
    public function department()
    {
        return $this->belongsTo(Departments::class, 'deptid');
    }
}
