<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IctAccessResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'privilegeId',
        'email',
        'requested_email_address',
        'userId',
        'hmisId',
        'aruti',
        'edocs',
        'sap',
        'ASPId',
        'nhifId',
        'hardware_request',
        'network_folder',
        'folder_privilege',
        'active_drt',
        'start_date',
        'end_date',
        'VPN',
        'pbax',
        'status',
        'physical_access',
        'delete_status',
        'type_id',
        'user_type',
        'action_required',
        'access_required',
        'access_key_card_id',
    ];

    protected $casts = [
        'hmisId' => 'array',
        'edocs' => 'array',
        'access_key_card_id' => 'array',
        // 'start_date' => 'date',
        // 'end_date' => 'date',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }

    // public function type(){
    //     return $this->belongsTo(UserType::class, 'type_id');
    // }

    // public function category(){
    //     return $this->belongsTo(UserCategory::class, 'category_id');
    // }

    public function privi() {
        return $this->belongsTo(PrivilegeLevel::class, 'privilegeId');
    }

    public function hmis() {
    return HMISAccessLevel::whereIn('id', $this->hmisId ?: [])->get();
    }

    public function nhif() {
        return $this->belongsTo(NhifQualification::class, 'nhifId');
    }

    public function arutiPrivilege() {
        return $this->belongsTo(PrivilegeLevel::class, 'aruti');
    }

    public function arutiLevel() {
        return $this->belongsTo(ArutiLevel::class, 'aruti');
    }

    public function edocsLevels() {
        return EdocsLevel::whereIn('id', $this->edocs ?: [])->get();
    }

    public function sapPrivilege() {
        return $this->belongsTo(PrivilegeLevel::class, 'sap');
    }

    public function vpnPrivilege() {
        return $this->belongsTo(PrivilegeLevel::class, 'VPN');
    }

    public function pbaxPrivilege() {
        return $this->belongsTo(PrivilegeLevel::class, 'pbax');
    }

    public function ad() {
        return $this->belongsTo(PrivilegeLevel::class, 'active_drt');
    }

    public function folderAccess() {
        return $this->belongsTo(PrivilegeLevel::class, 'folder_privilege'); // Fixed column name
    }

    public function ccbrtEmail() {
        return $this->belongsTo(PrivilegeLevel::class, 'email');
    }

    public function workflow() {
        return $this->hasOne(Workflow::class, 'ict_request_resource_id');
    }

    public function ASPLevel() {
        return $this->belongsTo(SAPLevels::class, 'ASPId');
    }

    public function accessKeyCard() {
        return $this->belongsTo(AccessKeyCard::class, 'access_key_card_id');
    }

}
