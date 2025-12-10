<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'departments', 
        'description', 
        'code',
        'status',
        'location',
        'HEC',
    ];

    // Cast departments to array for automatic conversion
    protected $casts = [
        'departments' => 'array',
    ];

    /**
     * Get the actual department models (if you need them)
     * This is optional but useful for displaying department names
     */
    public function departmentModels()
    {
        return Departments::whereIn('id', $this->departments ?? [])->get();
    }

    /**
     * A Division can have many Contracts
     */
    public function contracts()
    {
        return $this->hasMany(CcbrtContract::class);
    }

    /**
     * A Division belongs to one HEC (fixed relationship)
     */
    public function hec()
    {
        return $this->belongsTo(Hec::class, 'HEC');
    }
}