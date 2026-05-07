<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'payment_types',
        'is_active',
    ];

    protected $casts = [
        'payment_types' => 'array',
        'is_active' => 'boolean',
    ];
}
