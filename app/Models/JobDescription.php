<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDescription extends Model
{
protected $fillable = [
        'employee_type',
        'user_id',
        'dept_id',
        'job_title',
        'working_hours',
        'job_review_date',
        'jobs_responsible_for',
        'job_grade',
        'grade_job_holder',
        'technical_job_level',
        'region_location',
        'grade_difference_reason',
        'purpose',
        'reports_to',
        'accountabilities',
        'qualifications_experience',
        'competencies',
        'financial_details',
        'employees_managed',
        'stakeholders_managed',
        'org_structure',
    ];
    
    public function getOrgStructure1Attribute()
    {
        $parts = explode(', ', $this->org_structure);
        return isset($parts[0]) ? str_replace('Org Structure 1: ', '', $parts[0]) : 'no';
    }

    public function getOrgStructure2Attribute()
    {
        $parts = explode(', ', $this->org_structure);
        return isset($parts[1]) ? str_replace('Org Structure 2: ', '', $parts[1]) : 'no';
    }
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

    //  public function workflow()
    // {
    //     return $this->belongsTo(Workflow::class);
    // }
// In JobDescription.php
public function workflow()
{
    return $this->hasOne(Workflow::class, 'job_description_id');
}
    
}
