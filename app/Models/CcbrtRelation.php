<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcbrtRelation extends Model
{
    use HasFactory;

    protected $fillable = [
      'userId',
      'names',
      'position',
      'department',
      'relation',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'userId');
    }

    public function department(){
        return $this->belongsTo(Departments::class, 'department');
    }
    
}
