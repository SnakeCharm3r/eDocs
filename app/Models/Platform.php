<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = ['name', 'description', 'manager_user_id']; // include if you use mass-assign

    public function departments()
    {
        return $this->belongsToMany(\App\Models\Departments::class, 'department_platform', 'platform_id', 'department_id')
            ->withTimestamps();
    }

    public function units()
    {
        return $this->hasMany(\App\Models\Unit::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'platform_user');
    }

    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'manager_user_id');
    }
}
