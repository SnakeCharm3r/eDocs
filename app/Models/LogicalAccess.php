<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogicalAccess extends Model
{
    use HasFactory;
    protected $fillable = [
        'access_name',
        'delete_status'
    ];

    public function ict()
    {
    return $this->hasMany(IctAccessResource::class);
    }
}
