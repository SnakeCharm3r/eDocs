<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;
    protected $fillable = [
            'business_owner',
            'contact_person',
            'telephone_number',
            'email_address',
            'physical_address',
            'contract_file',
            'contract_total_value',
            'duration_years',
            'likelihood_rating',
            'impact_rating',
            'overall_risk_score',
        
    ];
}
