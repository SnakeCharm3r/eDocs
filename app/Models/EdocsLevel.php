<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EdocsLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'edocs_name',
        'edocs_status',
        'delete_status'
    ];
}
