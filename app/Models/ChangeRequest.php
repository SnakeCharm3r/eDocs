<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    use HasFactory;
protected $fillable = [
    'userId',
    'change_type',
    'change_category',
    'description_of_change',
    'service_type',
    'insurer_tariff_name',
    'reason_for_change',
    'priority',
    'supporting_document',
    'implementation_notes',
    'drug_name',
    'ms_number',
    'cash_price',
    'standard_nhif_price',
    'nhif_supplementary',
    'bot',
    'crdb',
    'word_vision_nmb',
    'premium_insurance',
    'nhif_codes',
    'additional_services',
    // New fields
    'tariff_type',
    'tariff_name',
    'tariff_category_id',
    'current_tariff_name',
    'service_action_type',
    'service_name',
    'service_category_id',
    'current_service_name',
    'service_prices',
    'price_item_name',
    'current_price',
    'new_price',
    'price_change_reason',
];

protected $casts = [
    'service_prices' => 'array',
    'current_price' => 'array',
    'new_price' => 'array',
];

    public function workflow()
    {
        return $this->hasOne(Workflow::class, 'change_request_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');  // Ensure 'userId' is the correct foreign key
    }

    public function tariffCategory()
    {
        return $this->belongsTo(TariffCategory::class, 'tariff_category_id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
