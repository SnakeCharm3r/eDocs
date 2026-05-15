<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HecContract extends Model
{
    use HasFactory;

    protected $table = 'hec_contracts';

    protected $fillable = [
        'contract_number',
        'title',
        'description',
        'contract_type',
        'division_id',
        'vendor_id',
        'file_path',
        'signed_contract_path',
        'terms_conditions_path',
        'sla_document_path',
        'cost',
        'currency',
        'start_date',
        'end_date',
        'duration_months',
        'status',
        'renewal_status',
        'impact_if_not_requested',
        'likelihood_rating',
        'contract_owner_id',
        'owner_email',
        'contract_source',
        'created_by',
        'expired_reminder_sent_at',
        'parent_contract_id',
        'renewed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
        'expired_reminder_sent_at' => 'datetime',
        'renewed_at' => 'datetime',
    ];

    /**
     * Contract Owner (HEC Member user)
     */
    public function contractOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contract_owner_id');
    }

    /**
     * Contract was created by a User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Contract belongs to a Division (CCBRT Entity)
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * Contract belongs to a Vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(CcbrtVendor::class);
    }

    /**
     * Original contract this was renewed from
     */
    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(HecContract::class, 'parent_contract_id');
    }

    /**
     * Renewal contracts created from this one
     */
    public function renewals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HecContract::class, 'parent_contract_id');
    }
}
