<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hslb extends Model
{
    use HasFactory;

    protected $fillable = [
        'userID',
        'loan_status',
        'form_iv_index_no',
        'hr_comment',
        'hr_member',
        'signature',
        'date',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'userId');
     }

    // public function hrMember()
    // {
    //     return $this->belongsTo(User::class, 'hr_member_id');
    // }
}
