<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFamilyDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'full_name',
        'relationship',
        'phone_number',
        'occupation',
        'next_of_kin',
        'emergency_contact',
        'delete_status'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'userId');
    }

}
