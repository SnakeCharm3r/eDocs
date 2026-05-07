<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentPolicy extends Model
{
    use HasFactory;
       protected $fillable = [
        'department_id',
        'title',
        'content',
        'created_by',

    ];
    public function departments()
    {
        return $this->belongsToMany(Departments::class, 'department_policy_department', 'department_policy_id', 'department_id');
    }

    // Relationship with User (creator)
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function department()
{
    return $this->belongsTo(Departments::class, 'department_id');
}

}
