<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorScore extends Model
{
    use HasFactory;

    // Table name (optional if Laravel can infer it)
    protected $table = 'vendor_scores';

    // Mass assignable fields
    protected $fillable = [
        'vendor_id',
        'contract_id',
        'scored_by',
        'score_value',
        'comments',
        'rating_type',
    ];

    /**
     * Score belongs to a Vendor
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

    /**
     * Score may belong to a Contract (optional)
     */
    public function contract()
    {
        return $this->belongsTo(CcbrtContract::class, 'contract_id');
    }
}
