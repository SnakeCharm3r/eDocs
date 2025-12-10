<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAdditionalInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'userId',
        'full_name',
        'relationship',
        'address',
        'mobile',
        'email',
        'occupation',
        'delete_status'
    ];

    public function user(){
        return $this->belongsTo(User::class,'userId');
    }
}
