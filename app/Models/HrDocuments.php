<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http;
use App\Http\Controllers\AnnouncementController;

class HrDocuments extends Model
{
    use HasFactory;
    // Specify that the primary key for this model is 'DocId', not the default 'id'
    protected $primaryKey = 'DocId';


    protected $fillable=[
        'DocId',
        'DocumentName',
        'DocumentPath',
        'Type'
        
    ];

    public function departments()
    {
        // return $this->belongsTo(Departments::class);
        return $this->belongsTo(Departments::class, 'deptId');
    }

}
