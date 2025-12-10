<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentWorkflowHistory extends Model
{
    use HasFactory;

    protected $table = 'recruitment_workflow_histories';

    protected $fillable = [
        'requisition_id',
        'step_name',
        'from_status',
        'to_status',
        'action',
        'attended_by',
        'comments',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the requisition this history belongs to
     */
    public function requisition(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequisition::class, 'requisition_id');
    }

    /**
     * Get the user who performed this action
     */
    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    /**
     * Get action badge class for UI
     */
    public function getActionBadgeClass(): string
    {
        return match($this->action) {
            'approved', 'no_objection', 'confirmed' => 'success',
            'rejected', 'objection', 'declined' => 'danger',
            'returned', 'needs_more_info' => 'warning',
            'submitted' => 'info',
            default => 'secondary',
        };
    }
}







