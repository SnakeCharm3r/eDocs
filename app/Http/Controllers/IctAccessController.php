<?php

namespace App\Http\Controllers;

use App\Mail\ApprovalRequestNotification;
use App\Models\IctAccessResource;
use App\Models\WorkFlowHistory;
use App\Models\HMISAccessLevel;
use App\Models\HealthDetails;
use App\Models\Hec;
use App\Models\NhifQualification;
use App\Models\PrivilegeLevel;
use App\Models\Remark;
use App\Models\SAPLevels;
use App\Models\User;
use App\Models\Workflow;
use App\Models\ArutiLevel;
use App\Models\EdocsLevel;
use App\Models\NetworkFolder;
use App\Models\AccessKeyCard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class IctAccessController extends Controller
{

    public function index()
    {
        // Load the authenticated user with their related models
        $user = Auth::user()->load('jobTitle', 'employmentType');

        // Check if user's job title is clinical
        $isClinicalDepartment = $user->jobTitle && $user->jobTitle->clinical_or_non_clinical === 'Clinical';
        
        // Get HR workflow history for license information (if clinical and has professional reg number)
        $hrWorkflowHistory = null;
        $licenseProviderName = '';
        if ($isClinicalDepartment && $user->professional_reg_number) {
            // Find HR workflow for this user
            $hrWorkflow = \App\Models\Workflow::where('hr_form', $user->id)->orderBy('id', 'desc')->first();
            if ($hrWorkflow) {
                // Get the most recent HR workflow history with license info
                $hrWorkflowHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $hrWorkflow->id)
                    ->whereNotNull('license_valid_until')
                    ->orderBy('id', 'desc')
                    ->first();
            }
            
            // Parse professional registration number to extract provider name
            $regNumber = $user->professional_reg_number ?? '';
            if (preg_match('/^([A-Z]+):\s*(.+)$/', $regNumber, $matches)) {
                $regType = $matches[1];
                $licenseProviders = [
                    'MCT' => 'Medical Council of Tanzania',
                    'TNMC' => 'Tanzania Nursing and Midwifery Council',
                    'TPB' => 'Tanzania Pharmacy Board',
                    'TPC' => 'Tanzania Physiotherapy Council',
                    'TMDC' => 'Tanzania Medical and Dental Council',
                    'Other' => 'Other'
                ];
                $licenseProviderName = $licenseProviders[$regType] ?? $regType;
            }
        }

        // Fetch the necessary resources
        $qualifications = NhifQualification::where('delete_status', 0)->get();
        $privileges = PrivilegeLevel::where('delete_status', 0)->get();
        $rmk = Remark::where('delete_status', 0)->get();
        $hmis = HMISAccessLevel::where('delete_status', 0)->orderBy('names', 'asc')->get();
        $acc = SAPLevels::all();
        $arutiLevels = ArutiLevel::where('delete_status', 0)->where('aruti_status', 'active')->orderBy('aruti_name', 'asc')->get();
        $edocsLevels = \App\Models\EdocsLevel::where('delete_status', 0)->where('edocs_status', 'active')->orderBy('edocs_name', 'asc')->get();
        $networkFolders = NetworkFolder::where('delete_status', 0)->where('folder_status', 'active')->orderBy('folder_name', 'asc')->get();
        $accessKeyCards = AccessKeyCard::where('delete_status', 0)->where('status', 'active')->orderBy('card_number', 'asc')->get();
        $ictAccessResources = IctAccessResource::where('delete_status', 0)->get();


        // Display the ICT access form
        return view('ict-access-form.index', compact(
            'user',
            'qualifications',
            'privileges',
            'rmk',
            'hmis',
            'ictAccessResources',
            'acc',
            'arutiLevels',
            'edocsLevels',
            'networkFolders',
            'accessKeyCards',
            'isClinicalDepartment',
            'hrWorkflowHistory',
            'licenseProviderName'
        ));
    }


    public function create()
    {
        $acc = SAPLevels::all();
        $user = Auth::user()->load('department', 'employmentType');
        $qualifications = NhifQualification::where('delete_status', 0)->get();
        $privileges = PrivilegeLevel::where('delete_status', 0)->get();
        $hmis = HMISAccessLevel::where('delete_status', 0)->orderBy('names', 'asc')->get();
        $arutiLevels = ArutiLevel::where('delete_status', 0)->where('aruti_status', 'active')->orderBy('aruti_name', 'asc')->get();
        $edocsLevels = \App\Models\EdocsLevel::where('delete_status', 0)->where('edocs_status', 'active')->orderBy('edocs_name', 'asc')->get();
        $networkFolders = NetworkFolder::where('delete_status', 0)->where('folder_status', 'active')->orderBy('folder_name', 'asc')->get();
        $accessKeyCards = AccessKeyCard::where('delete_status', 0)->where('status', 'active')->orderBy('card_number', 'asc')->get();

        return view('ict-access-form.index', compact('qualifications', 'privileges', 'hmis', 'user', 'acc', 'arutiLevels', 'edocsLevels', 'networkFolders', 'accessKeyCards'));
    }

    public function store(Request $request)
    {
        // Build validation rules conditionally based on enabled sections
        $rules = [
            'userId'            => 'required|exists:users,id',
            'user_type'         => 'required|in:New,Existing',
            'action_required'   => 'required|in:Create,Update',
            'access_required'   => 'required|in:Grant,Revoke',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after_or_equal:start_date',
        ];

        // Domain Access - required if enabled
        if ($request->has('enable_domain') && $request->input('enable_domain') == '1') {
            $rules['active_drt'] = 'required|exists:privilege_levels,id';
        } else {
            $rules['active_drt'] = 'nullable|exists:privilege_levels,id';
        }

        // Email Access - required if enabled
        if ($request->has('enable_email') && $request->input('enable_email') == '1') {
            $rules['email'] = 'required|exists:privilege_levels,id';
            $rules['requested_email_address'] = 'nullable|email|max:255';
        } else {
            $rules['email'] = 'nullable|exists:privilege_levels,id';
            $rules['requested_email_address'] = 'nullable|email|max:255';
        }

        // Network Folder - required if enabled
        if ($request->has('enable_network_folder') && $request->input('enable_network_folder') == '1') {
            $rules['network_folder'] = 'required|string';
            $rules['other_network_folder'] = 'nullable|string|max:255';
            $rules['folder_privilege'] = 'required|exists:privilege_levels,id';
        } else {
            $rules['network_folder'] = 'nullable|string';
            $rules['other_network_folder'] = 'nullable|string|max:255';
            $rules['folder_privilege'] = 'nullable|exists:privilege_levels,id';
        }

        // HMIS Access - required if enabled
        if ($request->has('enable_hmis') && $request->input('enable_hmis') == '1') {
            $rules['hmisId'] = 'required|array|min:1';
            $rules['hmisId.*'] = 'exists:h_m_i_s_access_levels,id';
        } else {
            $rules['hmisId'] = 'nullable|array';
            $rules['hmisId.*'] = 'nullable|exists:h_m_i_s_access_levels,id';
        }

        // Aruti HR MIS - required if enabled
        if ($request->has('enable_aruti') && $request->input('enable_aruti') == '1') {
            $rules['aruti'] = 'required|exists:aruti_levels,id';
        } else {
            $rules['aruti'] = 'nullable|exists:aruti_levels,id';
        }

        // VPN Access - required if enabled
        if ($request->has('enable_vpn') && $request->input('enable_vpn') == '1') {
            $rules['VPN'] = 'required|exists:privilege_levels,id';
        } else {
            $rules['VPN'] = 'nullable|exists:privilege_levels,id';
        }

        // SAP ERP Access - required if enabled
        if ($request->has('enable_sap') && $request->input('enable_sap') == '1') {
            $rules['ASPId'] = 'required|exists:s_a_p_levels,id';
        } else {
            $rules['ASPId'] = 'nullable|exists:s_a_p_levels,id';
        }

        // PABX Access - required if enabled
        if ($request->has('enable_pbax') && $request->input('enable_pbax') == '1') {
            $rules['pbax'] = 'required|exists:privilege_levels,id';
        } else {
            $rules['pbax'] = 'nullable|exists:privilege_levels,id';
        }

        // Access Key Card - required if enabled
        if ($request->has('enable_keycard') && $request->input('enable_keycard') == '1') {
            $rules['access_key_card_id'] = 'required|array|min:1';
            $rules['access_key_card_id.*'] = 'exists:access_key_cards,id';
        } else {
            $rules['access_key_card_id'] = 'nullable|array';
            $rules['access_key_card_id.*'] = 'nullable|exists:access_key_cards,id';
        }

        // Hardware Request - optional, but validate if provided
        $rules['hardware_request'] = 'nullable|array';
        $rules['hardware_request.*'] = 'nullable|string';

        // Validate the request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        // If user selected "Other" folder, require the specific folder value
        if ($request->input('enable_network_folder') == '1' && $request->input('network_folder') === 'Other') {
            $request->validate([
                'other_network_folder' => 'required|string|max:255',
            ]);
        }

        try {
            \DB::beginTransaction();

            // Get the user to auto-populate dates if not provided
            $user = User::findOrFail($request->input('userId'));
            
            // Auto-populate start_date and end_date from user table if not provided
            $startDate = $request->input('start_date');
            if (empty($startDate) && $user->starting_date) {
                $startDate = \Carbon\Carbon::parse($user->starting_date)->format('Y-m-d');
            }
            
            $endDate = $request->input('end_date');
            if (empty($endDate) && $user->ending_date) {
                $endDate = \Carbon\Carbon::parse($user->ending_date)->format('Y-m-d');
            }

            // Detect if requester is IT department or requesting super admin access
            $isITDepartment = $request->input('is_it_department') === '1';
            $activeDrtPrivilege = PrivilegeLevel::find($request->input('active_drt'));
            $emailPrivilege = PrivilegeLevel::find($request->input('email'));
            
            $isRequestingSuperAdmin = false;
            if ($activeDrtPrivilege && $activeDrtPrivilege->prv_name === 'Super Administrator') {
                $isRequestingSuperAdmin = true;
            }
            if ($emailPrivilege && $emailPrivilege->prv_name === 'Super Administrator') {
                $isRequestingSuperAdmin = true;
            }

            // Log the detection for approval workflow
            \Log::info('ICT Access Request', [
                'user_id' => $user->id,
                'is_it_department' => $isITDepartment,
                'is_requesting_super_admin' => $isRequestingSuperAdmin,
                'user_type' => $request->input('user_type'),
                'access_required' => $request->input('access_required'),
            ]);

            $hardwareRequest = $request->has('hardware_request')
                ? implode(',', $request->input('hardware_request'))
                : null;

            $networkFolderValue = null;
            if ($request->has('enable_network_folder') && $request->input('enable_network_folder') == '1') {
                $networkFolderValue = $request->input('network_folder');
                if ($networkFolderValue === 'Other') {
                    $networkFolderValue = $request->input('other_network_folder');
                }
            }

            // Get default privilege level (User) for backward compatibility
            $defaultPrivilege = PrivilegeLevel::where('prv_name', 'User')->first();
            $privilegeId = $defaultPrivilege ? $defaultPrivilege->id : null;

            $ict = IctAccessResource::create([
                'privilegeId'       => $privilegeId, // Set to default User for backward compatibility
                'email'             => ($request->has('enable_email') && $request->input('enable_email') == '1') ? $request->input('email') : null,
                'requested_email_address' => ($request->has('enable_email') && $request->input('enable_email') == '1')
                    ? $request->input('requested_email_address')
                    : null,
                'userId'            => $request->input('userId'),
                'hmisId'            => ($request->has('enable_hmis') && $request->input('enable_hmis') == '1' && $request->has('hmisId') && !empty($request->input('hmisId'))) ? (is_array($request->input('hmisId')) ? $request->input('hmisId') : []) : null,
                'aruti'             => ($request->has('enable_aruti') && $request->input('enable_aruti') == '1') ? $request->input('aruti') : null,
                'edocs'             => ($request->has('enable_edocs') && $request->input('enable_edocs') == '1' && $request->has('edocs') && !empty($request->input('edocs'))) ? (is_array($request->input('edocs')) ? $request->input('edocs') : []) : null,
                'ASPId'             => ($request->has('enable_sap') && $request->input('enable_sap') == '1') ? $request->input('ASPId') : null,
                'nhifId'            => null, // Removed from form, set to null for backward compatibility
                'hardware_request'  => ($request->has('enable_hardware') && $request->input('enable_hardware') == '1') ? $hardwareRequest : null,
                'network_folder'    => $networkFolderValue,
                'folder_privilege'  => ($request->has('enable_network_folder') && $request->input('enable_network_folder') == '1') ? $request->input('folder_privilege') : null,
                'active_drt'        => ($request->has('enable_domain') && $request->input('enable_domain') == '1') ? $request->input('active_drt') : null,
                'user_type'         => $request->input('user_type'),
                'action_required'   => $request->input('action_required'),
                'access_required'   => $request->input('access_required'),
                'VPN'               => ($request->has('enable_vpn') && $request->input('enable_vpn') == '1') ? $request->input('VPN') : null,
                'pbax'              => ($request->has('enable_pbax') && $request->input('enable_pbax') == '1') ? $request->input('pbax') : null,
                'status'            => $request->input('status', 0),
                'physical_access'   => $request->input('physical_access', null),
                'access_key_card_id' => ($request->has('enable_keycard') && $request->input('enable_keycard') == '1' && $request->has('access_key_card_id') && !empty($request->input('access_key_card_id'))) ? (is_array($request->input('access_key_card_id')) ? $request->input('access_key_card_id') : []) : null,
                'start_date'        => $startDate,
                'end_date'          => $endDate,
                'delete_status'     => 0,
            ]);
              //dd($ict);
            $workflow = $this->saveWorkflow([
                'user_id'                  => Auth::id(),
                'ict_request_resource_id' => $ict->id,
                'work_flow_status'        => 'sent to approval',
                'work_flow_completed'     => 0,
            ]);

            $this->saveWorkflowHistory([
                'work_flow_id'  => $workflow->id,
                'forwarded_by'  => Auth::id(),
                'attended_by'   => Auth::id(),
                'status'        => '1',
                'remark'        => 'ICT Access Resource',
                'attend_date'   => now()->format('d F Y'),
                'parent_id'     => null,
            ]);

            $approverResult = Cache::remember("approver_user_" . Auth::id(), 300, function () {
                return $this->findLineManagerForRequesterDepartment();
            });

            if (!$approverResult) {
                throw new \Exception('Approver not found');
            }

            // Check if CEO request - goes directly to HR
            $isCeoRequest = ($approverResult === 'hr_direct');
            $approversForEmail = [];
            
            if ($isCeoRequest) {
                // CEO request - send to all HR users
                $hrUsers = User::role('hr')->get();
                if ($hrUsers->isEmpty()) {
                    throw new \Exception('No HR users found for CEO request approval');
                }
                
                foreach ($hrUsers as $hrUser) {
                    $this->forwardWorkflowHistory([
                        'work_flow_id'  => $workflow->id,
                        'forwarded_by'  => Auth::id(),
                        'attended_by'   => $hrUser->id,
                        'status'        => '0',
                        'remark'        => 'CEO Request - Forwarded directly to HR for approval',
                        'attend_date'   => now()->format('d F Y'),
                        'parent_id'     => $workflow->id,
                    ]);
                    $approversForEmail[] = $hrUser;
                }
            } else {
                // Normal flow - single approver
                $this->forwardWorkflowHistory([
                    'work_flow_id'  => $workflow->id,
                    'forwarded_by'  => Auth::id(),
                    'attended_by'   => $approverResult->id,
                    'status'        => '0',
                    'remark'        => 'Forwarded for approval',
                    'attend_date'   => now()->format('d F Y'),
                    'parent_id'     => $workflow->id,
                ]);
                $approversForEmail[] = $approverResult;
            }

            \DB::commit();

            // Build request details summary
            $requestSummary = [];
            
            // Domain Access
            if ($ict->active_drt) {
                $drtPrivilege = PrivilegeLevel::find($ict->active_drt);
                if ($drtPrivilege) {
                    $requestSummary[] = "Domain Access: " . $drtPrivilege->prv_name;
                }
            }
            
            // Email Access
            if ($ict->email) {
                $emailPrivilege = PrivilegeLevel::find($ict->email);
                if ($emailPrivilege) {
                    $requestSummary[] = "Email Access: " . $emailPrivilege->prv_name;
                }
            }
            if (!empty($ict->requested_email_address)) {
                $requestSummary[] = "Requested Email Address: " . $ict->requested_email_address;
            }
            
            // Network Folder Access
            if ($ict->network_folder) {
                $folderPrivilege = PrivilegeLevel::find($ict->folder_privilege);
                $folderAccess = "Network Folder: " . $ict->network_folder;
                if ($folderPrivilege) {
                    $folderAccess .= " (" . $folderPrivilege->prv_name . ")";
                }
                $requestSummary[] = $folderAccess;
            }
            
            // Additional System Access
            $additionalSystems = [];
            
            // HealthAI HMIS
            if ($ict->hmisId) {
                $hmisIds = is_string($ict->hmisId) ? json_decode($ict->hmisId, true) : $ict->hmisId;
                if (is_array($hmisIds) && !empty($hmisIds)) {
                    $hmisLevels = \DB::table('h_m_i_s_access_levels')->whereIn('id', $hmisIds)->pluck('names')->toArray();
                    if (!empty($hmisLevels)) {
                        $additionalSystems[] = "HealthAI HMIS: " . implode(', ', $hmisLevels);
                    }
                }
            }
            
            // Aruti HR MIS
            if ($ict->aruti && $ict->aruti != '0') {
                $arutiLevel = \App\Models\ArutiLevel::find($ict->aruti);
                if ($arutiLevel) {
                    $additionalSystems[] = "Aruti HR MIS: " . $arutiLevel->aruti_name;
                }
            }
            
            // eDocs
            if ($ict->edocs) {
                $edocsIds = is_string($ict->edocs) ? json_decode($ict->edocs, true) : $ict->edocs;
                if (is_array($edocsIds) && !empty($edocsIds)) {
                    $edocsLevels = \App\Models\EdocsLevel::whereIn('id', $edocsIds)->pluck('edocs_name')->toArray();
                    if (!empty($edocsLevels)) {
                        $additionalSystems[] = "eDocs System: " . implode(', ', $edocsLevels);
                    }
                }
            }
            
            // VPN
            if ($ict->VPN && $ict->VPN != '0') {
                $vpnPrivilege = PrivilegeLevel::find($ict->VPN);
                if ($vpnPrivilege) {
                    $additionalSystems[] = "Network Access (VPN): " . $vpnPrivilege->prv_name;
                }
            }
            
            // SAP
            if ($ict->ASPId && $ict->ASPId != '0') {
                $sapLevel = \App\Models\SAPLevels::find($ict->ASPId);
                if ($sapLevel) {
                    $additionalSystems[] = "SAP ERP Access: " . $sapLevel->access_name;
                }
            }
            
            // PABX
            if ($ict->pbax && $ict->pbax != '0') {
                $pbaxPrivilege = PrivilegeLevel::find($ict->pbax);
                if ($pbaxPrivilege) {
                    $additionalSystems[] = "Call Manager-PABX: " . $pbaxPrivilege->prv_name;
                }
            }
            
            // Access Key Cards
            if ($ict->access_key_card_id) {
                $keyCardIds = is_string($ict->access_key_card_id) ? json_decode($ict->access_key_card_id, true) : $ict->access_key_card_id;
                if (is_array($keyCardIds) && !empty($keyCardIds)) {
                    $keyCards = \App\Models\AccessKeyCard::whereIn('id', $keyCardIds)->pluck('card_number')->toArray();
                    if (!empty($keyCards)) {
                        $additionalSystems[] = "Access Key Cards: " . implode(', ', $keyCards);
                    }
                }
            }
            
            if (!empty($additionalSystems)) {
                $requestSummary = array_merge($requestSummary, $additionalSystems);
            }
            
            // Hardware Request
            if ($ict->hardware_request) {
                $requestSummary[] = "Hardware: " . $ict->hardware_request;
            }

            // Send email (queued) to all approvers (do not fail request if mail fails)
            $requester = Auth::user();
            $requesterFullName = trim(($requester->fname ?? '') . ' ' . ($requester->lname ?? '')) ?: $requester->username;
            
            foreach ($approversForEmail as $approver) {
                try {
                    if (!empty($approver?->email)) {
                        Mail::to($approver->email)->queue(new ApprovalRequestNotification($approver, [
                            'forwarded_by' => $requesterFullName,
                            'request'      => "IT Access Form",
                            'requestDate'  => now()->format('d F Y'),
                            'user_type' => $ict->user_type ?? null,
                            'access_required' => $ict->access_required ?? null,
                            'request_summary' => $requestSummary,
                        ]));
                    }
                } catch (\Throwable $mailEx) {
                    \Log::error('ICT Access: Failed to dispatch approval email', [
                        'ict_access_resource_id' => $ict->id,
                        'approver_user_id' => $approver?->id,
                        'to' => $approver?->email,
                        'error' => $mailEx->getMessage(),
                    ]);
                    // Don't fail the request if email fails
                }
            }

            Alert::success('Successfully', 'IT access request added');
            return redirect()->route('request.index')->with('success', 'IT access form request submitted successfully');
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error storing IT Access Resource', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token'])
            ]);

            Alert::error('Failed to submit IT access form request', $e->getMessage());
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }


    public function saveWorkflow($input)
    {
        // dd($input);
        return Workflow::create($input);
    }


    public function saveWorkflowHistory($input)
    {
        return WorkFlowHistory::create($input);
    }



    public function findLineManagerForRequesterDepartment()
    {

        try {
            // Check if the requester is the CEO - CEO requests go directly to HR
            if (Auth::user()->hasRole('ceo')) {
                // Return 'hr' as a special indicator that form should go to HR users
                return 'hr_direct';
            }
            
            $hecRoles = ['coo', 'cfo', 'cms', 'ccdro'];
            if (Auth::user()->hasAnyRole($hecRoles)) {
                $ceo = User::role('ceo')->first();
                if (!$ceo) {
                    throw new \Exception('CEO approver not found for HEC member request.');
                }
                return $ceo;
            }

            // Get the role name of the requester (Line Manager)
            $requesterRole = Auth::user()->getRoleNames()->first();
            // dd( $requesterRole);
            if ($requesterRole === 'line-manager') {
                // Get the department and hec_id of the Line Manager
                $lineManager = Auth::user();
                //  dd( $lineManager);
                $lineManagerDepartment = $lineManager->department;
                // dd($lineManagerDepartment);
                $hec_id = $lineManagerDepartment->hec_id;
                // dd($hec_id);
                // tafuta hec level name sahihi (COO, CFO, CMS, CCDRO)
                $hec = $lineManagerDepartment->hec;
                //    dd($hec);
                if ($hec) {
                    //ringaniche hec_level na role husika
                    $approverRoleName = null;

                    // check role na zipe uwezo kulingana heck_level
                    switch ($hec->hec_level_name) {
                        case 'COO':
                            $approverRoleName = 'coo';
                            break;
                        case 'CFO':
                            $approverRoleName = 'cfo';
                            break;
                        case 'CMS':
                            $approverRoleName = 'cms';
                            break;
                        case 'CCDRO':
                            $approverRoleName = 'ccdro';
                            break;
                        default:
                            throw new \Exception('No valid hec_level_name found for this department.');
                    }
                    // dd($approverRoleName);
                    // Find the approver with the relevant role
                    $approver = User::role($approverRoleName)->first();
                    //  dd($approver);
                    if ($approver) {
                        return $approver;
                    } else {
                        throw new \Exception('No approver found for the requested role.');
                    }
                } else {
                    throw new \Exception('No matching hec_id found for this department.');
                }
            } else {
                // Role name of the Line Manager (approver role)
                $approverRoleName = 'line-manager';

                // Department ID of the Requester
                $requesterDepartmentId = Auth::user()->deptId; // Get the department ID of the requester

                // Query to find the Line Manager in the same department as the requester
                $approver = User::role($approverRoleName)
                    ->where('deptId', $requesterDepartmentId) // Look for line-manager in the same department
                    ->first();

                // If no approver found, throw an exception
                if (!$approver) {
                    throw new \Exception('Line Manager for Requester department not found or unauthorized');
                }

                return $approver;
            }
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \Log::error('Error finding approver: ' . $e->getMessage());

            // Rethrow the exception for further handling
            throw $e;
        }
    }



    public function forwardWorkflowHistory($input)
    {
        return WorkFlowHistory::create($input);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ictAccessResource = IctAccessResource::findOrFail($id);

        return view('ict-access-form.show', compact('ictAccessResource'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($workflowId)
    {
        // Fetch the workflow record by ID
        $workflow = Workflow::findOrFail($workflowId);
        //   dd($workflow);
        // Fetch the associated ICT Access Resource using the relationship
        $ict = $workflow->ictAccessResource;
        //   dd($ict);
        if (!$ict) {
            // Handle the case where the ICT Access Resource is not found
            Alert::error('ICT Access Resource not found', 'Error');
            return redirect()->route('request.index')->with('error', 'ICT Access Resource not found');
        }

        // Fetch necessary data for the form
        $acc = SAPLevels::all();
        $user = Auth::user()->load('department', 'employmentType');
        $qualifications = NhifQualification::where('delete_status', 0)->get();
        $privileges = PrivilegeLevel::where('delete_status', 0)->get();
        $hmis = HMISAccessLevel::where('delete_status', 0)->get();

        // Pass the data to the view
        return view('ict-access-form.edit', compact('ict', 'qualifications', 'privileges', 'hmis', 'user','acc'));
    }




    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'privilegeId' => 'required|exists:privilege_levels,id',
            'userId' => 'required|exists:users,id',
            'hmisId' => 'required|exists:h_m_i_s_access_levels,id',
            'aruti' => 'required|exists:privilege_levels,id',
            'ASPId' => 'required|exists:s_a_p_levels,id',
            'nhifId' => 'required|exists:nhif_qualifications,id',
            'active_drt' => 'required|exists:privilege_levels,id',
            'VPN' => 'required|exists:privilege_levels,id',
            'pbax' => 'required|exists:privilege_levels,id',
        ]);
        // dd($validator);

        if ($validator->fails()) {
            \Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors(),
            ]);
        }

        try {
            \DB::transaction(function () use ($request, $id) {
                $ict = IctAccessResource::findOrFail($id);

                // Update ICT access form data
                $hardwareRequest = $request->input('hardware_request') ? implode(',', $request->input('hardware_request')) : null;

                $ict->update([
                    'privilegeId' => $request->input('privilegeId'),
                    'email' => $request->input('email'),
                    'requested_email_address' => $request->input('requested_email_address'),
                    'userId' => $request->input('userId'),
                    'hmisId' => $request->input('hmisId'),
                    'aruti' => $request->input('aruti'),
                    'ASPId' => $request->input('ASPId'),
                    'nhifId' => $request->input('nhifId'),
                    'hardware_request' => $hardwareRequest,
                    'network_folder' => $request->input('network_folder'),
                    'folder_privilege' => $request->input('folder_privilege'),
                    'active_drt' => $request->input('active_drt'),
                    'VPN' => $request->input('VPN'),
                    'pbax' => $request->input('pbax'),
                    'status' => $request->input('status'),
                    'physical_access' => $request->input('physical_access'),
                    'starting_date' => $request->input('starting_date'),
                    'ending_date' => $request->input('ending_date'),
                    'delete_status' => 0,
                ]);

                $workflow = Workflow::where('ict_request_resource_id', $ict->id)->first();

                if (!$workflow) {
                    throw new \Exception('Workflow not found for this request.');
                }

                $rejectedHistory = WorkflowHistory::where('work_flow_id', $workflow->id)
                    ->where('status', -1)
                    ->latest()
                    ->first();

                if ($rejectedHistory) {
                    $rejectedHistory->status = 0;
                    $rejectedHistory->remark = 'Resubmitted after update';
                    $rejectedHistory->attend_date = Carbon::now()->format('d F Y');
                    $rejectedHistory->save();
                } else {
                    throw new \Exception('WorkflowHistory not found for Workflow ID ' . $workflow->id . ' and status -1');
                }

                // Get the next approver (e.g., line manager)
                $approver = $this->findLineManagerForRequesterDepartment();

                // Forward it again for approval
                // $this->forwardWorkflowHistory([
                //     'work_flow_id' => $workflow->id,
                //     'forwarded_by' => Auth::user()->id,
                //     'attended_by' => $approver->id,
                //     'status' => 0,
                //     'remark' => 'Forwarded for approval after update',
                //     'attend_date' => Carbon::now()->format('d F Y'),
                //     'parent_id' => $rejectedHistory->id,
                // ]);

                $requester = Auth::user();
                $requesterFullName = trim(($requester->fname ?? '') . ' ' . ($requester->lname ?? '')) ?: $requester->username;
                try {
                    if (!empty($approver?->email)) {
                        Mail::to($approver->email)->queue(new ApprovalRequestNotification($approver, [
                            'forwarded_by' => $requesterFullName,
                            'request'      => "IT Access Form",
                            'requestDate'  => now()->format('d F Y'),
                        ]));
                    }
                } catch (\Throwable $mailEx) {
                    \Log::error('ICT Access: Failed to dispatch resubmission email', [
                        'ict_access_resource_id' => $ict->id,
                        'approver_user_id' => $approver?->id,
                        'to' => $approver?->email,
                        'error' => $mailEx->getMessage(),
                    ]);
                    // Don't fail the resubmission if email fails
                }
            });


            Alert::success('Successfully', 'Resubmitted Successfully');
            return redirect()->route('request.index');
        } catch (\Exception $e) {
            \Log::error('Update failed: ' . $e->getMessage());
            Alert::error('Update failed', 'An error occurred while resubmitting the request');
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ictAccessResource = IctAccessResource::findOrFail($id);
        $ictAccessResource->delete_status = 1;
        $ictAccessResource->save();

        return redirect()->route('ict-access-form.index')->with('success', 'ICT Access Resource deleted successfully.');
    }
}
