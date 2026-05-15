<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Sop extends Model
{
    use HasFactory;

    protected $fillable = [
        'deptId',
        'division_id',
        'owner_department_id',
        'title',
        'document_code',
        'description',
        'global',
        'pdf_path',
        'file_type',
        'effective_date',
        'next_review_date',
        'expiry_date',
        'version',
        'status',
        'created_by',
        'updated_by',
        'archived_at',
        'archived_by',
        'view_count',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'next_review_date' => 'date',
        'expiry_date' => 'date',
        'archived_at' => 'datetime',
        'global' => 'boolean',
    ];

    /**
     * Current review date, falling back to legacy expiry_date for older records.
     */
    public function getCurrentReviewDateAttribute(): ?Carbon
    {
        return $this->next_review_date ?? $this->expiry_date;
    }

    /**
     * Relationship: SOP belongs to a Division/Entity (legacy single division)
     */
    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    /**
     * Relationship: SOP can belong to multiple Divisions/Entities
     */
    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'division_sop', 'sop_id', 'division_id')
            ->withTimestamps();
    }

    /**
     * Relationship: SOP owner/managing department (HOD / Line Manager of this department manages the SOP)
     */
    public function ownerDepartment()
    {
        return $this->belongsTo(Departments::class, 'owner_department_id');
    }

    /**
     * Relationship: SOP belongs to many Departments
     */
    public function departments()
    {
        return $this->belongsToMany(Departments::class, 'department_sop', 'sop_id', 'department_id')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    /**
     * Alias for departments relationship
     */
    public function departmentssop()
    {
        return $this->belongsToMany(Departments::class, 'department_sop');
    }

    /**
     * Relationship: SOP created by a User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: SOP updated by a User
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relationship: SOP archived by a User
     */
    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Check if SOP is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if SOP is archived
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Check if SOP is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        if ($this->current_review_date && $this->current_review_date->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if SOP is expiring soon (within 30 days)
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (!$this->current_review_date) {
            return false;
        }

        return $this->current_review_date->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): ?int
    {
        if (!$this->current_review_date) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->current_review_date->startOfDay(), false);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'archived' => 'bg-secondary',
            'expired' => 'bg-danger',
            default => 'bg-light text-dark',
        };
    }

    /**
     * Get expiry status badge
     */
    public function getExpiryBadgeClass(): string
    {
        if ($this->isExpired()) {
            return 'bg-danger';
        }

        if ($this->isExpiringSoon(30)) {
            return 'bg-warning text-dark';
        }

        return 'bg-success';
    }

    /**
     * Get expiry status label
     */
    public function getExpiryStatusLabel(): string
    {
        $days = $this->daysUntilExpiry();

        if ($days === null) {
            return 'No expiry set';
        }

        if ($days < 0) {
            return 'Expired ' . abs($days) . ' days ago';
        }

        if ($days === 0) {
            return 'Expires today';
        }

        if ($days <= 30) {
            return 'Expires in ' . $days . ' days';
        }

        return 'Valid';
    }

    /**
     * Scope: Active SOPs only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Archived SOPs only
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope: Expired SOPs only
     */
    public function scopeExpired($query)
    {
        $today = now()->startOfDay()->toDateString();

        return $query->where(function ($q) use ($today) {
            $q->where('status', 'expired')
                ->orWhereRaw('COALESCE(next_review_date, expiry_date) < ?', [$today]);
            });
    }

    /**
     * Scope: SOPs expiring soon
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        $today = now()->startOfDay()->toDateString();
        $until = now()->addDays($days)->toDateString();

        return $query->where('status', 'active')
            ->whereRaw('COALESCE(next_review_date, expiry_date) IS NOT NULL')
            ->whereRaw('COALESCE(next_review_date, expiry_date) BETWEEN ? AND ?', [$today, $until]);
    }

    /**
     * Scope: SOPs for a specific department
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('global', true)
                ->orWhereHas('departments', function ($dq) use ($departmentId) {
                    $dq->where('departments.id', $departmentId);
                });
        });
    }

    /**
     * Scope: SOPs for a specific division/entity
     */
    public function scopeForDivision($query, $divisionId)
    {
        return $query->where('division_id', $divisionId);
    }

    /**
     * Archive the SOP
     */
    public function archive($userId): void
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
            'archived_by' => $userId,
        ]);
    }

    /**
     * Generate document code
     */
    public static function generateDocumentCode($divisionId = null, $departmentId = null): string
    {
        $prefix = 'SOP';

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
