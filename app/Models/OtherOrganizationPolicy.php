<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherOrganizationPolicy extends Model
{
    use HasFactory;

    protected $table = 'other_organization_policies';

    protected $fillable = [
        'title',
        'document_code',
        'organization_code',
        'description',
        'content',
        'content_type',
        'pdf_path',
        'division_id',
        'policy_category_id',
        'is_global',
        'status',
        'effective_date',
        'next_review_date',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_global'        => 'boolean',
        'effective_date'   => 'date',
        'next_review_date' => 'date',
    ];

    /**
     * Relationship: Policy belongs to a Category
     */
    public function category()
    {
        return $this->belongsTo(PolicyCategory::class, 'policy_category_id');
    }

    /**
     * Relationship: Policy belongs to a Division/Entity (legacy single division)
     */
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /**
     * Relationship: Policy can belong to multiple Divisions/Entities
     */
    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'division_other_organization_policy', 'other_organization_policy_id', 'division_id')
            ->withTimestamps();
    }

    /**
     * Relationship: Policy belongs to many Departments
     */
    public function departments()
    {
        return $this->belongsToMany(Departments::class, 'other_organization_policy_department', 'other_organization_policy_id', 'department_id')
            ->withTimestamps()
            ->withPivot('created_by');
    }

    /**
     * Relationship: Policy created by a User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Policy updated by a User
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if Policy is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if Policy is archived
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Check if Policy is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if Policy is global (for all users)
     */
    public function isGlobal(): bool
    {
        return $this->is_global === true;
    }

    /**
     * Scope: Active policies only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Archived policies only
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope: Draft policies only
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Generate document code
     */
    public static function generateDocumentCode($divisionId = null): string
    {
        $prefix = 'ORG-POL';

        if ($divisionId) {
            $division = Division::find($divisionId);
            if ($division && $division->code) {
                $prefix .= '-' . strtoupper($division->code);
            }
        }

        $count = self::count() + 1;
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
