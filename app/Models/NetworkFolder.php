<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkFolder extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_name',
        'folder_status',
        'description',
        'delete_status'
    ];
}
