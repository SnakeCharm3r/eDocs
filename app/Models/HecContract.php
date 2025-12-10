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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
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
}
