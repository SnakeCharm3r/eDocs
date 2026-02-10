<?php

namespace App\Models;

use App\Models\VendorScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CcbrtVendor extends Model
{
    use HasFactory;

    // Mass assignable fields
    protected $fillable = [
        'name',
        'type',
        'contact_person',
        'contact_email',
        'contact_phone',
        'alternative_phone',
        'website',
        'address',
        'rating',
        'owner_name',
        'registration_number',
        'tax_number',
        'industry',
        'status',
        'attachments',
        'years_in_business',
        'number_of_employees',
        'country',
        'bank_name',
        'bank_account_number',
        'payment_terms',
        'currency',
        'notes',
    ];

    /**
     * A vendor can have many contracts
     */
    public function contracts()
    {
        return $this->hasMany(CcbrtContract::class, 'vendor_id');
    }

    /**
     * A vendor can have many scores
     */
    public function scores()
    {
        return $this->hasMany(VendorScore::class, 'vendor_id');
    }

    /**
     * Get the average rating from all scores
     */
    public function getAverageRatingAttribute()
    {
        $scores = $this->scores()->where('rating_type', 'overall')->get();
        if ($scores->isEmpty()) {
            return $this->rating ?? 0; // Fallback to old rating field
        }
        return round($scores->avg('score_value'), 2);
    }

    /**
     * Get the latest rating
     */
    public function getLatestRatingAttribute()
    {
        $latestScore = $this->scores()->where('rating_type', 'overall')->latest()->first();
        return $latestScore ? $latestScore->score_value : ($this->rating ?? 0);
    }
}
