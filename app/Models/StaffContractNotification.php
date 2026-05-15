<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffContractNotification extends Model
{
    protected $fillable = [
        'user_id',
        'milestone',
        'contract_end_date',
        'sent_at',
    ];

    protected $casts = [
        'contract_end_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
