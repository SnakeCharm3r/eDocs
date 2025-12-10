<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'physical_disability',
        'blood_group',
        'illness_history',
        'health_insurance',
        'insur_name',
        'insur_no',
        'allergies',
        'delete_status',
    ];

    public function user(){
        return $this->hasMany(User::class, 'userId');
    }
}
