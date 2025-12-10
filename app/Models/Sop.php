<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sop extends Model
{
    use HasFactory;
    protected $fillable = [
        'deptId',
        'title',
        'global',
        'pdf_path',
        'created_by'
    ];

    public function departments()
    {
        return $this->belongsToMany(Departments::class, 'department_sop', 'sop_id', 'department_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
        public function departmentssop()
    {
        return $this->belongsToMany(Departments::class, 'department_sop');
    }
    public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}


}
