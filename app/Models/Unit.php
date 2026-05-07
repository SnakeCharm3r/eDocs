<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'platform_id',
        'name',
        'description',
        'is_active',
        'locum_hours',
        'incharge_user_id',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'unit_user', 'unit_id', 'user_id')->withTimestamps();
    }

    public function incharge()
    {
        return $this->belongsTo(User::class, 'incharge_user_id');
    }
    
}
