<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateOfService extends Model
{
    protected $fillable = [
        'user_id', 'created_by', 'approved_by', 'certificate_number',
        'issue_date', 'date_of_joining', 'last_working_day',
        'position_held', 'department', 'duties_description',
        'remarks', 'coo_signature_path', 'approver_signature_path', 'status',
        'initialized_at', 'initialized_by',
    ];

    protected $casts = [
        'issue_date'      => 'date',
        'date_of_joining' => 'date',
        'last_working_day'=> 'date',
        'initialized_at'  => 'datetime',
    ];

    public function initializer()
    {
        return $this->belongsTo(User::class, 'initialized_by');
    }

    public function isInitialized(): bool
    {
        return $this->initialized_at !== null;
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateCertificateNumber(): string
    {
        $year  = now()->year;
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'COS-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
