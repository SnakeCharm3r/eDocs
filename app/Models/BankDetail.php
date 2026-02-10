<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'userId',
        'bank_name',
        'branch_name',
        'branch_address',
        'account_name',
        'account_number',
        'swift_code',
        'bank_mobile_number'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
