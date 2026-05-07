<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantModel extends Model
{
    use HasFactory;
    public $fillable = [
     'application_date',
     'consultant_full_name',
     'consultant_address',
     'consultant_mobile',
     'qualification',
     'year_of_experience',
     'hosted_dept',
     'startDate',
     'endDate',
     'createdBy'
    ];

    public function creator(){
        return $this->belongsTo(User::class, 'createdBy');
    }

    public function hostedDept(){
        return $this->belongsTo(Departments::class, 'hosted_dept');
    }
}
