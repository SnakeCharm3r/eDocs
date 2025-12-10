<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BioUser extends Model
{

    protected $connection = 'bio'; // Use your biotime connection
    protected $table = 'personnel_employee';
    protected $primaryKey = 'userid';  // Or your actual PK
    public $timestamps = false; // if no created_at/updated_at columns
}
