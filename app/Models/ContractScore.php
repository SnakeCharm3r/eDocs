<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractScore extends Model
{
    use HasFactory;

    // Table name (optional if following Laravel conventions)
    protected $table = 'contract_scores';

    // Mass assignable fields
    protected $fillable = [
        'contract_id',
        'vendor_id',
        'scored_by',
        'score_value',
        'comments',// give the score of the contract
    ];

    /**
     * Score belongs to a Contract
     */
    public function contract()
    {
        return $this->belongsTo(CcbrtContract::class, 'contract_id');
    }

    /**
     * Score belongs to a Vendor (optional)
     */
    public function vendor()
    {
        return $this->belongsTo(CcbrtVendor::class, 'vendor_id');
    }

    /**
     * Score belongs to a User who scored it
     */
    public function scorer()
    {
        return $this->belongsTo(User::class, 'scored_by');
    }
}
