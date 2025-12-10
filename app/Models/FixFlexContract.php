<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixFlexContract extends Model
{
    use HasFactory;
     protected $fillable = [
        'user_id',
        'name',
        'date_of_birth',
        'gender',
        'nationality',
        'job_title_id',
        'duty_station',
        'duration',
        'salary_fixed',
        'salary_flexible',
        'type',
        'working_hours',
        'probation',
        'contract_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }
    public function department()
{
    return $this->belongsTo(Departments::class);
}

}
