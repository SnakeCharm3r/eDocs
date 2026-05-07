<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hec extends Model
{
    use HasFactory;

    protected $fillable = [
      'hec_level_name',
      'descriptions',
      'createdBy',
      'updatedBy'

    ];

    public function departments(){
        return $this->hasMany(Departments::class, 'hec_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'createdBy');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updatedBy');
    }

}
