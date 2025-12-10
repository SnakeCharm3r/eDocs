<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    /**
     * Get the user that owns the session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active sessions (not expired)
     * @param int $lifetime Lifetime in minutes
     */
    public function scopeActive($query, $lifetime = 2)
    {
        return $query->where('last_activity', '>=', now()->subMinutes($lifetime));
    }

    /**
     * Clean up expired sessions
     * @param int $lifetime Lifetime in minutes
     */
    public static function cleanupExpired($lifetime = 2)
    {
        return static::where('last_activity', '<', now()->subMinutes($lifetime))->delete();
    }
}
