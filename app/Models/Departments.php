<?php

namespace App\Models;

use App\Models\DeptPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departments extends Model
{
    use HasFactory;


    protected $fillable = [
        'dept_name',
        'description',
        'hec_id',
        'hec_member_id',
        'clinical_or_non_clinical',
        'has_three_level_approval',
        'has_incharge_platform_flow',
        'has_incharge_lm_hec_flow',
        'has_oncall_three_level_approval',
        'delete_status',
    ];

    public function user()
    {
        return $this->hasMany(User::class, 'deptId');
    }
    public function sops()
    {
        return $this->hasMany(Sop::class, 'deptId', 'department_sop', 'department_id', 'sop_id');
    }

    public function sopsdept()
    {
        return $this->belongsToMany(Sop::class, 'department_sop');
    }

    public function relations()
    {
        return $this->hasMany(Relation::class, 'department');
    }

    public function hec()
    {
        return $this->belongsTo(Hec::class, 'hec_id');
    }

    /**
     * Get the HEC member (user) assigned to this department
     */
    public function hecMember()
    {
        return $this->belongsTo(User::class, 'hec_member_id');
    }

    /**
     * Get all recruitment requisitions for this department
     */
    public function recruitmentRequisitions()
    {
        return $this->hasMany(RecruitmentRequisition::class, 'department_id');
    }

    public function jobDescriptions()
    {
        return $this->hasMany(JobDescription::class);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
    public function departmentPolicies()
    {
        return $this->belongsToMany(DepartmentPolicy::class, 'department_policy_department', 'department_id', 'department_policy_id', 'policy_id');
    }
    public function policies()
    {
        return $this->belongsToMany(DepartmentPolicy::class, 'department_policy_department');
    }

    public function hostedDept()
    {
        return $this->hasMany(ConsultantModel::class, 'hosted_dept');
    }

    // public function users()
    // {
    //     return $this->hasMany(User::class, 'deptId');
    // }

    public function head()
    {
        return $this->hasOne(User::class, 'deptId')
            ->whereHas('roles', function ($q) {
                $q->where('name', 'line-manager');
            });
    }
    // public function platforms()
    // {
    //     return $this->belongsToMany(Platform::class, 'department_platform')
    //         ->withTimestamps();
    // }

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'department_platform', 'department_id', 'platform_id')
            ->withTimestamps();
    }

    /**
     * A Department can belong to many Divisions/Entities (many-to-many)
     */
    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'division_department', 'department_id', 'division_id')
            ->withTimestamps();
    }

    /**
     * A Department can have many Job Titles
     */
    public function jobTitles()
    {
        return $this->hasMany(JobTitle::class, 'deptId');
    }
}
