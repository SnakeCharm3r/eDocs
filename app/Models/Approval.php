<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'ict_access_resource_id',
         'stage', 
         'approver_id', 
         'status'
        ];


    public function ictAccessResource()
    {
        return $this->belongsTo(IctAccessResource::class);
    }
}
