<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArutiLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'aruti_name',
        'aruti_status',
        'delete_status'
    ];
}
