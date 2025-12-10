<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FailedLoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'ip_address',
        'user_agent',
        'attempted_at',
        'success',
        'failure_reason',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'success' => 'boolean',
    ];

    /**
     * Get failed attempts for a username in the last N minutes
     */
    public static function getRecentFailedAttempts($username, $minutes = 60)
    {
        return self::where('username', $username)
            ->where('success', false)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Get failed attempts by IP address
     */
    public static function getFailedAttemptsByIp($ipAddress, $minutes = 60)
    {
        return self::where('ip_address', $ipAddress)
            ->where('success', false)
            ->where('attempted_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderBy('attempted_at', 'desc')
            ->get();
    }

    /**
     * Get all failed login attempts with pagination
     */
    public static function getAllFailedAttempts($perPage = 50)
    {
        return self::where('success', false)
            ->orderBy('attempted_at', 'desc')
            ->paginate($perPage);
    }
}
