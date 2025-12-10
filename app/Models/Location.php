<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'address',
        'contact_person',
        'contact_phone',
        'contact_email',
        'is_active',
        'display_order',
        'delete_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function accessKeyCards()
    {
        return $this->hasMany(AccessKeyCard::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('delete_status', '!=', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
