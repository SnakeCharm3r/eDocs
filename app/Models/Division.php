<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'name',
        'department',
        'description',
        'code',
        'status',
        'location',
        'HEC',
        'delete_status',
    ];

    /**
     * A Division can have many Departments (many-to-many)
     */
    public function departments()
    {
        return $this->belongsToMany(Departments::class, 'division_department', 'division_id', 'department_id')
            ->withTimestamps();
    }

    /**
     * A Division can have many Contracts
     */
    public function contracts()
    {
        return $this->hasMany(CcbrtContract::class);
    }

    public function Hecs()
    {
        return $this->hasMany(Hec::class);
    }
}
