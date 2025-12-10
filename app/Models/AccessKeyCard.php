<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessKeyCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_number',
        'user_id',
        'status',
        'notes',
        'assigned_by',
        'delete_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('delete_status', '!=', 1);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active');
    }
}

