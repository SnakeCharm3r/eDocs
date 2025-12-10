<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivilegeLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'prv_name',
        'prv_status',
        'delete_status',
        'can_use_for_domain_access',
        'can_use_for_email_access',
        'can_use_for_vpn_access',
        'can_use_for_pbax_access',
    ];

    protected $casts = [
        'can_use_for_domain_access' => 'boolean',
        'can_use_for_email_access' => 'boolean',
        'can_use_for_vpn_access' => 'boolean',
        'can_use_for_pbax_access' => 'boolean',
    ];

    // public function prv(){
    //     return $this->belongsTo(IctAccess::class);
    // }
}
