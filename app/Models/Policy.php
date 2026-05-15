<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'document_code',
        'description',
        'content',
        'content_type',
        'pdf_path',
        'division_id',
        'policy_category_id',
        'is_global',
        'visible_to_users',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'visible_to_users' => 'boolean',
    ];

    /**
     * Relationship: Policy belongs to a Category
     */
    public function category()
    {
        return $this->belongsTo(PolicyCategory::class, 'policy_category_id');
    }

    /**
     * Relationship: Policy belongs to a Division/Entity
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
        return $this->belongsToMany(Division::class, 'division_policy', 'policy_id', 'division_id')
            ->withTimestamps();
    }

    /**
     * Relationship: Policy belongs to many Departments
     */
    public function departments()
    {
        return $this->belongsToMany(Departments::class, 'policy_department', 'policy_id', 'department_id')
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
     * Scope: Policies for a specific department
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('is_global', true)
                ->orWhereHas('departments', function ($dq) use ($departmentId) {
                    $dq->where('departments.id', $departmentId);
                });
        });
    }

    /**
     * Scope: Policies for a specific division/entity
     */
    public function scopeForDivision($query, $divisionId)
    {
        return $query->where('division_id', $divisionId);
    }

    /**
     * Generate document code
     */
    public static function generateDocumentCode($divisionId = null): string
    {
        $prefix = 'POL';

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
