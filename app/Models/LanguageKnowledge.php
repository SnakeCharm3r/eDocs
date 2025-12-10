<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LanguageKnowledge extends Model
{
    use HasFactory;

    protected $fillable =[
        'language',
        'speaking',
        'reading',
        'writing',
        'userId',
        'delete_status',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'userId');
    }
}
