<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property string|null $pdf_path
 * @property int $userId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement query()
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Announcement whereUserId($value)
 */
	class Announcement extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \App\Models\IctAccessResource|null $ictAccessResource
 * @method static \Illuminate\Database\Eloquent\Builder|Approval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Approval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Approval query()
 */
	class Approval extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string $bank_name
 * @property string $branch_name
 * @property string $branch_address
 * @property string $account_name
 * @property string $account_number
 * @property string|null $swift_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereAccountName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereBranchAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereBranchName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereSwiftCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankDetail whereUserId($value)
 */
	class BankDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string|null $names
 * @property string|null $position
 * @property \App\Models\Departments|null $department
 * @property string|null $relation
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation query()
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereNames($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereRelation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CcbrtRelation whereUserId($value)
 */
	class CcbrtRelation extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string $description_of_change
 * @property string $service_type
 * @property string|null $insurer_tariff_name
 * @property string|null $service_price
 * @property string|null $old_price
 * @property string|null $new_price
 * @property string $reason_for_change
 * @property string $priority
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereDescriptionOfChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereInsurerTariffName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereNewPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereOldPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereReasonForChange($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereServicePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereServiceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChangeRequest whereUserId($value)
 */
	class ChangeRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $ccbrt_id_card
 * @property string $ccbrt_name_tag
 * @property string $nhif_cards
 * @property string $work_permit_cancelled
 * @property string $residence_permit_cancelled
 * @property string $repaid_salary_advance
 * @property string $loan_balances_informed
 * @property string $repaid_outstanding_imprest
 * @property string $changing_room_keys
 * @property string $office_keys
 * @property string $mobile_phone
 * @property string $camera
 * @property string $ccbrt_uniforms
 * @property string $office_car_keys
 * @property string|null $other_items
 * @property string $laptop_returned
 * @property string $access_card_returned
 * @property string $domain_account_disabled
 * @property string $email_account_disabled
 * @property string $telephone_pin_disabled
 * @property string $openclinic_account_disabled
 * @property string $sap_account_disabled
 * @property string $aruti_account_disabled
 * @property int|null $userId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereAccessCardReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereArutiAccountDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereCamera($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereCcbrtIdCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereCcbrtNameTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereCcbrtUniforms($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereChangingRoomKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereDomainAccountDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereEmailAccountDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereLaptopReturned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereLoanBalancesInformed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereMobilePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereNhifCards($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereOfficeCarKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereOfficeKeys($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereOpenclinicAccountDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereOtherItems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereRepaidOutstandingImprest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereRepaidSalaryAdvance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereResidencePermitCancelled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereSapAccountDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereTelephonePinDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClearanceForm whereWorkPermitCancelled($value)
 */
	class ClearanceForm extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $requested_resource_id
 * @property string $work_flow_status
 * @property int $work_flow_completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow query()
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereRequestedResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereWorkFlowCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow whereWorkFlowStatus($value)
 */
	class Clearance_work_flow extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $work_flow_id
 * @property int|null $forwarded_by
 * @property int|null $attended_by
 * @property int|null $who_approve
 * @property string|null $status
 * @property string|null $remark
 * @property string|null $attend_date
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history query()
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereAttendDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereAttendedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereForwardedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereWhoApprove($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Clearance_work_flow_history whereWorkFlowId($value)
 */
	class Clearance_work_flow_history extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \App\Models\Departments|null $department
 * @property-read \App\Models\JobTitle|null $jobTitle
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Contract newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Contract newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Contract query()
 */
	class Contract extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $dept_name
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $hec_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contract> $contracts
 * @property-read int|null $contracts_count
 * @property-read \App\Models\Hec|null $hec
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JobDescription> $jobDescriptions
 * @property-read int|null $job_descriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sop> $sops
 * @property-read int|null $sops_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $user
 * @property-read int|null $user_count
 * @method static \Illuminate\Database\Eloquent\Builder|Departments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Departments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Departments query()
 * @method static \Illuminate\Database\Eloquent\Builder|Departments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Departments whereDeptName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Departments whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Departments whereHecId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Departments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Departments whereUpdatedAt($value)
 */
	class Departments extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $employment_type
 * @property string $description
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $user
 * @property-read int|null $user_count
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes whereEmploymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmploymentTypes whereUpdatedAt($value)
 */
	class EmploymentTypes extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Form newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Form newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Form query()
 */
	class Form extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string|null $names
 * @property string|null $status
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel whereNames($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HMISAccessLevel whereUpdatedAt($value)
 */
	class HMISAccessLevel extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string $physical_disability
 * @property string|null $blood_group
 * @property string|null $illness_history
 * @property string $health_insurance
 * @property string|null $insur_name
 * @property string|null $insur_no
 * @property string|null $allergies
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $user
 * @property-read int|null $user_count
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereAllergies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereBloodGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereHealthInsurance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereIllnessHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereInsurName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereInsurNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails wherePhysicalDisability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HealthDetails whereUserId($value)
 */
	class HealthDetails extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $hec_level_name
 * @property string $descriptions
 * @property int|null $createdBy
 * @property int|null $updatedBy
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Departments> $departments
 * @property-read int|null $departments_count
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder|Hec newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hec newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hec query()
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereDescriptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereHecLevelName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Hec whereUpdatedBy($value)
 */
	class Hec extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $DocId
 * @property string $DocumentName
 * @property string $DocumentPath
 * @property string $Type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Departments|null $departments
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments query()
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments whereDocId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments whereDocumentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments whereDocumentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|HrDocuments whereUpdatedAt($value)
 */
	class HrDocuments extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Hslb newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hslb newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Hslb query()
 */
	class Hslb extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards query()
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IDCards whereUserId($value)
 */
	class IDCards extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $privilegeId
 * @property string|null $email
 * @property int $userId
 * @property int $hmisId
 * @property int|null $nhifId
 * @property string|null $aruti
 * @property string|null $active_drt
 * @property string|null $VPN
 * @property string|null $pbax
 * @property string|null $sap
 * @property int|null $ASPId
 * @property string|null $hardware_request
 * @property string|null $network_folder
 * @property int|null $folder_privilege
 * @property string|null $status
 * @property string|null $start_date
 * @property string|null $end_date
 * @property string|null $physical_access
 * @property int $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SAPLevels|null $ASPLevel
 * @property-read \App\Models\PrivilegeLevel|null $ad
 * @property-read \App\Models\PrivilegeLevel|null $arutiPrivilege
 * @property-read \App\Models\PrivilegeLevel|null $ccbrtEmail
 * @property-read \App\Models\PrivilegeLevel|null $folderAccess
 * @property-read \App\Models\HMISAccessLevel|null $hmis
 * @property-read \App\Models\NhifQualification|null $nhif
 * @property-read \App\Models\PrivilegeLevel|null $pbaxPrivilege
 * @property-read \App\Models\PrivilegeLevel|null $privi
 * @property-read \App\Models\PrivilegeLevel|null $sapPrivilege
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\PrivilegeLevel|null $vpnPrivilege
 * @property-read \App\Models\Workflow|null $workflow
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource query()
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereASPId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereActiveDrt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereAruti($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereFolderPrivilege($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereHardwareRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereHmisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereNetworkFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereNhifId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource wherePbax($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource wherePhysicalAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource wherePrivilegeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereSap($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IctAccessResource whereVPN($value)
 */
	class IctAccessResource extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|IdCardRequest whereUserId($value)
 */
	class IdCardRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \App\Models\Departments|null $department
 * @property-read \App\Models\JobTitle|null $jobTitle
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|JobDescription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JobDescription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JobDescription query()
 */
	class JobDescription extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $deptId
 * @property string $job_title
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contract> $contracts
 * @property-read int|null $contracts_count
 * @property-read \App\Models\Departments|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JobDescription> $jobDescriptions
 * @property-read int|null $job_descriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $user
 * @property-read int|null $user_count
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle query()
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle whereDeptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle whereJobTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|JobTitle whereUpdatedAt($value)
 */
	class JobTitle extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string $language
 * @property string $speaking
 * @property string $reading
 * @property string $writing
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge query()
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereReading($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereSpeaking($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LanguageKnowledge whereWriting($value)
 */
	class LanguageKnowledge extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Level newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Level newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Level query()
 */
	class Level extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string|null $form_iv_index
 * @property string $has_loan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration query()
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration whereFormIvIndex($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration whereHasLoan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LoanDeclaration whereUserId($value)
 */
	class LoanDeclaration extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IctAccessResource> $ict
 * @property-read int|null $ict_count
 * @method static \Illuminate\Database\Eloquent\Builder|LogicalAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogicalAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LogicalAccess query()
 */
	class LogicalAccess extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string|null $status
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification query()
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifQualification whereUpdatedAt($value)
 */
	class NhifQualification extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration query()
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|NhifRegistration whereUserId($value)
 */
	class NhifRegistration extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Policy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Policy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Policy query()
 * @method static \Illuminate\Database\Eloquent\Builder|Policy whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Policy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Policy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Policy whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Policy whereUpdatedAt($value)
 */
	class Policy extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $prv_name
 * @property string $prv_status
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel query()
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel wherePrvName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel wherePrvStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PrivilegeLevel whereUpdatedAt($value)
 */
	class PrivilegeLevel extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $rmk_name
 * @property string $status
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Remark newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Remark newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Remark query()
 * @method static \Illuminate\Database\Eloquent\Builder|Remark whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Remark whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Remark whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Remark whereRmkName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Remark whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Remark whereUpdatedAt($value)
 */
	class Remark extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property-read \App\Models\Departments|null $department
 * @property-read \App\Models\JobTitle|null $jobTitle
 * @property-read \App\Models\User|null $replacementUser
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Requisition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Requisition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Requisition query()
 */
	class Requisition extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $access_name
 * @property string $access_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IctAccessResource> $level
 * @property-read int|null $level_count
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels query()
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels whereAccessName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels whereAccessStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SAPLevels whereUpdatedAt($value)
 */
	class SAPLevels extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int|null $deptId
 * @property string $title
 * @property string|null $pdf_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $global
 * @property-read \App\Models\Departments|null $departments
 * @method static \Illuminate\Database\Eloquent\Builder|Sop newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sop newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sop query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sop whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sop whereDeptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sop whereGlobal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sop whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sop wherePdfPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sop whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sop whereUpdatedAt($value)
 */
	class Sop extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property string $fname
 * @property string|null $mname
 * @property string $lname
 * @property string|null $DOB
 * @property string $username
 * @property string|null $gender
 * @property string|null $marital_status
 * @property string|null $region
 * @property string $email
 * @property string|null $nida
 * @property string|null $driving_license
 * @property string|null $transport_id
 * @property string|null $voting_id
 * @property string|null $other_document
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $religion
 * @property string|null $mobile
 * @property int|null $job_title
 * @property string|null $home_address
 * @property string|null $district
 * @property string|null $professional_reg_number
 * @property string|null $place_of_birth
 * @property string|null $nationality
 * @property string|null $house_no
 * @property string|null $street
 * @property string|null $popular_landmark
 * @property string|null $plot_no
 * @property string|null $box_no
 * @property string|null $emp_id
 * @property int $deptId
 * @property int $employment_typeId
 * @property string|null $employee_cv
 * @property string|null $NIN
 * @property string|null $nssf_no
 * @property string|null $tin_no
 * @property string|null $passport_no
 * @property string|null $marriage_certificate
 * @property string|null $divorced_certificate
 * @property string|null $domicile
 * @property string|null $profile_picture
 * @property string|null $signature
 * @property string|null $starting_date
 * @property string|null $ending_date
 * @property mixed $password
 * @property string|null $delete_status
 * @property string $status
 * @property string $conflict_officer_role
 * @property string|null $officer_details
 * @property string $financial_interest
 * @property string|null $financial_details
 * @property string $other_interests
 * @property string|null $interest_details
 * @property string $primary_employer_ccbrt
 * @property string|null $primary_employer_details
 * @property string $court_proceedings
 * @property string|null $court_details
 * @property string|null $hr_detail_declare
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $ccbrt_code
 * @property string|null $hec_allocation
 * @property string|null $education_level
 * @property string|null $form_4_certificate
 * @property string|null $form_6_certificate
 * @property string|null $diploma_certificate
 * @property string|null $bachelor_certificate
 * @property string|null $masters_certificate
 * @property string|null $phd_certificate
 * @property string|null $diploma_transcript
 * @property string|null $bachelor_transcript
 * @property string|null $masters_transcript
 * @property string|null $phd_transcript
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChangeRequest> $Change
 * @property-read int|null $change_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CcbrtRelation> $ccbrtRelation
 * @property-read int|null $ccbrt_relation_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Contract> $contracts
 * @property-read int|null $contracts_count
 * @property-read \App\Models\Departments|null $department
 * @property-read \App\Models\EmploymentTypes|null $employmentType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HealthDetails> $healthDetails
 * @property-read int|null $health_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hec> $hecsCreated
 * @property-read int|null $hecs_created_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Hec> $hecsUpdated
 * @property-read int|null $hecs_updated_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\JobDescription> $jobDescriptions
 * @property-read int|null $job_descriptions_count
 * @property-read \App\Models\JobTitle|null $jobTitle
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LanguageKnowledge> $languageKnowledge
 * @property-read int|null $language_knowledge_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserFamilyDetails> $userFamilyDetails
 * @property-read int|null $user_family_details_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBachelorCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBachelorTranscript($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBoxNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCcbrtCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereConflictOfficerRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCourtDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCourtProceedings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDOB($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDiplomaCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDiplomaTranscript($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDistrict($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDivorcedCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDomicile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDrivingLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEducationLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmployeeCv($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmploymentTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEndingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFinancialDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFinancialInterest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereForm4Certificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereForm6Certificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereHecAllocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereHomeAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereHouseNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereHrDetailDeclare($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereInterestDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereJobTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMarriageCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMastersCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMastersTranscript($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNIN($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNida($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereNssfNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereOfficerDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereOtherDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereOtherInterests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassportNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhdCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhdTranscript($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePlaceOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePlotNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePopularLandmark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePrimaryEmployerCcbrt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePrimaryEmployerDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfessionalRegNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereProfilePicture($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRegion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStartingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereStreet($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTinNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereTransportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereVotingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string $full_name
 * @property string $relationship
 * @property string $address
 * @property string|null $mobile
 * @property string|null $email
 * @property string|null $occupation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserAdditionalInfo whereUserId($value)
 */
	class UserAdditionalInfo extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserApproval newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserApproval newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserApproval query()
 */
	class UserApproval extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $userId
 * @property string $full_name
 * @property string $relationship
 * @property string|null $occupation
 * @property string|null $phone_number
 * @property int $next_of_kin
 * @property int $emergency_contact
 * @property string|null $delete_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereDeleteStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereEmergencyContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereFullName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereNextOfKin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserFamilyDetails whereUserId($value)
 */
	class UserFamilyDetails extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Usermanual newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Usermanual newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Usermanual query()
 */
	class Usermanual extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $work_flow_id
 * @property int|null $forwarded_by
 * @property int|null $attended_by
 * @property int|null $who_approve
 * @property string|null $status
 * @property string|null $remark
 * @property string|null $attend_date
 * @property int|null $parent_id
 * @property string|null $decision_date
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereAttendDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereAttendedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereDecisionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereForwardedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereRemark($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereWhoApprove($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkFlowHistory whereWorkFlowId($value)
 */
	class WorkFlowHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $ict_request_resource_id
 * @property string|null $hr_form
 * @property int|null $bank_form
 * @property int|null $heslb_form
 * @property int|null $nhif_form
 * @property int|null $id_form
 * @property string $work_flow_status
 * @property int $work_flow_completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\IctAccessResource|null $ictAccessResource
 * @property-read \App\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkFlowHistory> $workflowHistory
 * @property-read int|null $workflow_history_count
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow query()
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereBankForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereHeslbForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereHrForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereIctRequestResourceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereIdForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereNhifForm($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereWorkFlowCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Workflow whereWorkFlowStatus($value)
 */
	class Workflow extends \Eloquent {}
}

