<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractComment extends Model
{
    use HasFactory;

    // Table name (optional if Laravel follows conventions)
    protected $table = 'contract_comments';

    // Mass assignable fields
    protected $fillable = [
        'contract_id',
        'vendor_service_score',
        'comment',
        'created_by',
    ];

    /**
     * Comment belongs to a Contract
     */
    public function contract()
    {
        return $this->belongsTo(CcbrtContract::class, 'contract_id');
    }

    /**
     * Comment was created by a User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
