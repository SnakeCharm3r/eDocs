<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDeclaration extends Model
{
    use HasFactory;
    protected $fillable = [
        'userId',
        'form_iv_index',
        'has_loan',
    ];

    /**
     * Get the user associated with the loan declaration.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
