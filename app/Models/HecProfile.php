<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HecProfile extends Model
{
    use HasFactory;

    protected $table = 'hec_profiles';

    protected $fillable = [
        'user_id',
        'is_cfo',
        'is_coo',
        'is_cms',
        'is_ccdro',
    ];

    protected $casts = [
        'is_cfo' => 'boolean',
        'is_coo' => 'boolean',
        'is_cms' => 'boolean',
        'is_ccdro' => 'boolean',
    ];

    /**
     * Get the user this profile belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all HEC roles for this user
     */
    public function getHecRoles(): array
    {
        $roles = [];
        if ($this->is_cfo) $roles[] = 'CFO';
        if ($this->is_coo) $roles[] = 'COO';
        if ($this->is_cms) $roles[] = 'CMS';
        if ($this->is_ccdro) $roles[] = 'CCDRO';
        return $roles;
    }
}







