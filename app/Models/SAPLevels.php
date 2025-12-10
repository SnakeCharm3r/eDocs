<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SAPLevels extends Model
{
    use HasFactory;

    protected $fillable = [
        'access_name',
        'access_status',
    ];

    public function level(){
        return $this->hasMany(IctAccessResource::class, 'ASPId');
    }
}
