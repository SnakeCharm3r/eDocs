<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentPolicy extends Model
{
    use HasFactory;

    protected $table = 'department_policies';

    protected $fillable = [
        'title',
        'document_code',
        'description',
        'pdf_path',
        'department_id',
        'visible_to_all_staff',
        'status',
        'view_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'visible_to_all_staff' => 'boolean',
    ];

    /**
     * Policy belongs to a Department
     */
    public function department()
    {
        return $this->belongsTo(Departments::class, 'department_id');
    }

    /**
     * Policy created by a User
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Policy updated by a User
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }
}
