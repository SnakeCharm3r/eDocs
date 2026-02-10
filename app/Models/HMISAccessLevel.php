<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HMISAccessLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'names',
        'status',
        'delete_status'

    ];
}
