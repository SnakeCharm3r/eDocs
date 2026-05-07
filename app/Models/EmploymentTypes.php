<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentTypes extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_type',
        'description',
        'delete_status',

    ];

    public function user() {
        return $this->hasMany(User::class);
    }
}
