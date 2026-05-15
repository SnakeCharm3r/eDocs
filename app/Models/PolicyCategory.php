<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PolicyCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'section_number',
        'icon',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Policies in this category (policies table - CCBRT)
     */
    public function policies()
    {
        return $this->hasMany(Policy::class, 'policy_category_id');
    }

    /**
     * Other organization policies in this category
     */
    public function otherOrganizationPolicies()
    {
        return $this->hasMany(OtherOrganizationPolicy::class, 'policy_category_id');
    }
}
