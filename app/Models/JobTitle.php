<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
     // Fillable attributes to allow mass assignment
     protected $fillable = ['job_title', 'deptId', 'clinical_or_non_clinical'];

     public function department()
     {
         return $this->belongsTo(Departments::class, 'deptId');
     }

     public function user(){
        return $this->hasMany(User::class, 'job_title');
     }
     public function jobDescriptions()
    {
        return $this->hasMany(JobDescription::class);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
