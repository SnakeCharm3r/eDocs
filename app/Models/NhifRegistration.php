<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NhifRegistration extends Model
{
    use HasFactory;
    protected $fillable = ['userId'];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
