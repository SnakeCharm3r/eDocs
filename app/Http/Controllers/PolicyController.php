<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Policy;
use App\Models\OtherOrganizationPolicy;
use App\Models\PolicyCategory;
use App\Models\Division;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use App\Mail\OrganizationPolicyNotification;
use Barryvdh\DomPDF\Facade\Pdf;

class PolicyController extends Controller
{
    private const OTHER_ORG_EMAIL_NOTIFICATIONS_SETTING_KEY = 'other_org_policy_email_notifications_enabled';

    /**
     * Check if user is COO or Super Admin
     */
    private function canManagePolicies(): bool
    {
        $user = auth()->user();
        return $user->hasRole('coo') || $user->hasRole('super-admin');
    }

    /**
     * Check if user can manage CCBRT policies (HR, COO, or Super Admin)
     */
    private function canManageCCBRTPolicies(): bool
    {
        $user = auth()->user();
        return $user->hasRole('hr') || $user->hasRole('coo') || $user->hasRole('super-admin');
    }

    /**
     * Check if user can manage Other Organization policies (COO or Super Admin only)
     */
    private function canManageOtherOrgPolicies(): bool
    {
        $user = auth()->user();
        return $user->hasRole('coo') || $user->hasRole('super-admin');
    }

    /**
     * Check if user is Super Admin only
     */
    private function isSuperAdmin(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super-admin');
    }

    private function getSystemSetting(string $key, ?string $default = null): ?string
    {
        $value = DB::table('system_settings')
            ->whereRaw('`key` = ?', [$key])
            ->value('value');

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    private function setSystemSetting(string $key, string $value): void
    {
        $exists = DB::table('system_settings')
            ->whereRaw('`key` = ?', [$key])
            ->exists();

        if ($exists) {
            DB::table('system_settings')
                ->whereRaw('`key` = ?', [$key])
                ->update(['value' => $value, 'updated_at' => now()]);

            return;
        }

        DB::table('system_settings')->insert([
            'key' => $key,
            'value' => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function areOrganizationPolicyEmailsEnabled(): bool
    {
        return filter_var(
            $this->getSystemSetting(self::OTHER_ORG_EMAIL_NOTIFICATIONS_SETTING_KEY, '1'),
            FILTER_VALIDATE_BOOL
        );
    }

    private function resolveOtherOrganizationSubmissionStatus(Request $request, ?string $currentStatus = null): string
    {
        $submittedStatus = $request->input('submission_status');

        if ($submittedStatus === 'draft') {
            return 'draft';
        }

        if ($submittedStatus === 'active') {
            return 'active';
        }

        return $currentStatus ?? 'active';
    }

    private function policyIndexRouteParams(string $policyType, ?string $status = null): array
    {
        $params = ['type' => $policyType];

        if ($policyType !== 'other_organization') {
            return $params;
        }

        if ($status === 'draft') {
            $params['tab'] = 'drafts';
        } elseif ($status === 'archived') {
            $params['tab'] = 'archived';
        }

        return $params;
    }

    private function sendOrganizationPolicyNotifications(OtherOrganizationPolicy $policy, bool $isNew): int
    {
        if (!$this->areOrganizationPolicyEmailsEnabled()) {
            return 0;
        }

        $recipientCount = 0;

        $policy->load(['departments', 'division', 'divisions']);
        $recipients = $this->getRecipientsForOrganizationPolicy($policy);
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->queue(new OrganizationPolicyNotification($policy, $recipient, $isNew));
                $recipientCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $recipientCount;
    }

    /**
     * Staff signed policies list: all authenticated staff can view (no "view policies" permission).
     * Forwards to index with type=ccbrt.
     */
    public function staffSignedIndex(Request $request)
    {
        $request->merge(['type' => 'ccbrt', 'staff_signed_view' => true]);
        return $this->index($request);
    }

    /**
     * Display a listing of policies
     * Users see policies for their department/entity or global policies
     */
    public function index(Request $request)
    {
        // When no type is specified, default to CCBRT so Back from edit/create returns to the same list
        if (!$request->has('type')) {
            return redirect()->route('policies.index', ['type' => 'ccbrt'] + $request->query());
        }

        $user = auth()->user();
        $canManage = $this->canManagePolicies();
        $canManageCCBRT = $this->canManageCCBRTPolicies();
        $canManageOtherOrg = $this->canManageOtherOrgPolicies();

        // Separate queries: HR policies (policies table) and COO policies (other_organization_policies table)
        $ccbrtQuery = Policy::with(['departments', 'division', 'creator', 'updatedBy', 'category']);

        $otherOrgQuery = OtherOrganizationPolicy::with(['departments', 'division', 'divisions', 'creator', 'updatedBy', 'category']);

        // HR users can see all CCBRT policies (active and archived)
        // Non-managers only see active policies
        if (!$canManage) {
            // HR can see all CCBRT policies, others only see active
            if (!$user->hasRole('hr')) {
                $ccbrtQuery->where('status', 'active');
            }
            $otherOrgQuery->where('status', 'active');

            // Filter by user's department/entity (not for HR)
            if (!$user->hasAnyRole(['super-admin', 'admin', 'hr'])) {
                // Get user's department and its divisions
                $userDepartmentId = $user->deptId;
                $userDivisionIds = [];

                if ($userDepartmentId) {
                    $department = Departments::find($userDepartmentId);
                    if ($department) {
                        $userDivisionIds = $department->divisions()->pluck('divisions.id')->toArray();
                    }
                }

                $filterFunction = function ($q) use ($userDepartmentId, $userDivisionIds) {
                    // Global policies (for all users)
                    $q->where('is_global', true)
                        // Or policies specifically assigned to user's department
                        ->orWhereHas('departments', function ($dq) use ($userDepartmentId) {
                            if ($userDepartmentId) {
                                $dq->where('departments.id', $userDepartmentId);
                            } else {
                                $dq->whereRaw('1 = 0'); // No match if user has no department
                            }
                        })
                        // Or policies assigned to user's division/entity (only if no departments are specifically assigned)
                        ->orWhere(function ($divQ) use ($userDivisionIds) {
                            if (!empty($userDivisionIds)) {
                                // Policy must be in user's division (division_id or divisions pivot) AND have no specific departments assigned
                                $divQ->where(function ($q) use ($userDivisionIds) {
                                    $q->whereIn('division_id', $userDivisionIds)
                                        ->orWhereHas('divisions', function ($dq) use ($userDivisionIds) {
                                            $dq->whereIn('divisions.id', $userDivisionIds);
                                        });
                                })->whereDoesntHave('departments')->where('is_global', false);
                            } else {
                                $divQ->whereRaw('1 = 0');
                            }
                        });
                };

                // Staff-signed view: show ALL CCBRT policies to all staff; skip department/entity filter for CCBRT
                if (!$request->boolean('staff_signed_view')) {
                    $ccbrtQuery->where($filterFunction);
                }
                $otherOrgQuery->where($filterFunction);
            }
        } else {
            // Managers can filter by status
            if ($request->filled('status')) {
                $ccbrtQuery->where('status', $request->status);
                $otherOrgQuery->where('status', $request->status);
            }
        }

        // Filter by division/entity (include policies with that division in pivot)
        if ($request->filled('division_id')) {
            $requestedDivisionId = (int) $request->division_id;
            $ccbrtQuery->where('division_id', $requestedDivisionId);
            $otherOrgQuery->where(function ($q) use ($requestedDivisionId) {
                $q->where('division_id', $requestedDivisionId)
                    ->orWhereHas('divisions', function ($dq) use ($requestedDivisionId) {
                        $dq->where('divisions.id', $requestedDivisionId);
                    });
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $ccbrtQuery->whereHas('departments', function ($q) use ($request) {
                $q->where('departments.id', $request->department_id);
            });
            $otherOrgQuery->whereHas('departments', function ($q) use ($request) {
                $q->where('departments.id', $request->department_id);
            });
        }

        // Filter by title
        if ($request->filled('title')) {
            $ccbrtQuery->where('title', 'like', '%' . $request->title . '%');
            $otherOrgQuery->where('title', 'like', '%' . $request->title . '%');
        }

        // Filter by content type
        if ($request->filled('content_type')) {
            $ccbrtQuery->where('content_type', $request->content_type);
            $otherOrgQuery->where('content_type', $request->content_type);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $ccbrtQuery->where('policy_category_id', $request->category_id);
            $otherOrgQuery->where('policy_category_id', $request->category_id);
        }

        // Note: policy_type filter removed - using separate tables now

        // Separate active and archived policies
        $ccbrtActiveQuery = clone $ccbrtQuery;
        $ccbrtArchivedQuery = clone $ccbrtQuery;
        $otherOrgActiveQuery = clone $otherOrgQuery;
        $otherOrgArchivedQuery = clone $otherOrgQuery;
        $otherOrgDraftQuery = clone $otherOrgQuery;

        // Get active policies
        $ccbrtActiveQuery->where('status', 'active');
        $otherOrgActiveQuery->where('status', 'active');
        $ccbrtActivePolicies = $ccbrtActiveQuery->orderBy('created_at', 'desc')->get();
        $otherOrgActivePolicies = $otherOrgActiveQuery->orderBy('created_at', 'desc')->get();

        // Get archived policies (only for managers)
        $ccbrtArchivedPolicies = collect();
        $otherOrgArchivedPolicies = collect();
        $otherOrgDraftPolicies = collect();
        if ($canManage) {
            $ccbrtArchivedQuery->where('status', 'archived');
            $otherOrgArchivedQuery->where('status', 'archived');
            $ccbrtArchivedPolicies = $ccbrtArchivedQuery->orderBy('created_at', 'desc')->get();
            $otherOrgArchivedPolicies = $otherOrgArchivedQuery->orderBy('created_at', 'desc')->get();
        }

        if ($canManageOtherOrg) {
            $otherOrgDraftQuery->where('status', 'draft');
            $otherOrgDraftPolicies = $otherOrgDraftQuery->orderBy('created_at', 'desc')->get();
        }

        // For backward compatibility, also provide combined lists
        $ccbrtPolicies = $ccbrtQuery->orderBy('created_at', 'desc')->get();
        $otherOrgPolicies = $otherOrgQuery->orderBy('created_at', 'desc')->get();

        $divisions = Division::all();
        $departments = Departments::all();
        $policyCategories = PolicyCategory::orderBy('sort_order')->get();
        $isSuperAdmin = $this->isSuperAdmin();

        // Check if user is HR (and not COO/Super Admin)
        $isHR = $user->hasRole('hr') && !$user->hasRole('coo') && !$user->hasRole('super-admin');

        // Check if user is COO
        $isCOO = $user->hasRole('coo');

        // Show each section only according to permission: CCBRT for those who can view CCBRT, Org for those who can view Org
        $showCCBRTSection = $canManageCCBRT || !$canManage; // HR, COO, Super Admin, or regular staff (see filtered CCBRT)
        $activePolicyType = $request->get('type');
        // Organization policies: show to COO/Super Admin (manage) or to any user when viewing type=other_organization (filtered by their department/entity)
        $showOrgSection = $canManageOtherOrg || (($activePolicyType ?? '') === 'other_organization');
        if ($activePolicyType === 'ccbrt') {
            $showOrgSection = false;
        }
        if ($activePolicyType === 'other_organization') {
            $showCCBRTSection = false;
        }

        $otherOrgEmailsEnabled = $this->areOrganizationPolicyEmailsEnabled();

        return view('policies.index', compact(
            'ccbrtPolicies', 'otherOrgPolicies',
            'ccbrtActivePolicies', 'otherOrgActivePolicies',
            'ccbrtArchivedPolicies', 'otherOrgArchivedPolicies',
            'otherOrgDraftPolicies',
            'otherOrgEmailsEnabled',
            'departments', 'divisions', 'policyCategories', 'canManage', 'canManageCCBRT', 'canManageOtherOrg',
            'isSuperAdmin', 'isHR', 'isCOO',
            'showCCBRTSection', 'showOrgSection', 'activePolicyType'
        ));
    }

    public function updateOtherOrgEmailNotifications(Request $request)
    {
        if (!$this->canManageOtherOrgPolicies()) {
            Alert::error('Unauthorized', 'Only COO and Super Admin can manage organization policy email settings.');
            return redirect()->route('policies.index', ['type' => 'other_organization']);
        }

        $enabled = $request->boolean('other_org_policy_email_notifications_enabled');

        $this->setSystemSetting(
            self::OTHER_ORG_EMAIL_NOTIFICATIONS_SETTING_KEY,
            $enabled ? '1' : '0'
        );

        Alert::success(
            'Success',
            $enabled
                ? 'Organization policy email notifications are enabled.'
                : 'Organization policy email notifications are disabled. No emails will be sent when active organization policies are created or updated.'
        );

        $routeParams = ['type' => 'other_organization'];
        $tab = $request->input('tab');
        if (in_array($tab, ['active', 'drafts', 'archived'], true)) {
            $routeParams['tab'] = $tab;
        }

        return redirect()->route('policies.index', $routeParams);
    }

    /**
     * Show the form for creating a new policy
     * HR can create CCBRT policies, COO can create both types
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $isHR = $user->hasRole('hr') && !$user->hasRole('coo') && !$user->hasRole('super-admin');
        $isCOO = $user->hasRole('coo');
        $policyType = $request->get('type', 'ccbrt'); // Default to CCBRT

        // HR can only create CCBRT policies
        if ($isHR) {
            $policyType = 'ccbrt';
        }

        // COO defaults to other_organization policies
        if ($isCOO && !$request->has('type')) {
            $policyType = 'other_organization';
        }

        // Check permissions based on policy type
        if ($policyType === 'ccbrt' && !$this->canManageCCBRTPolicies()) {
            Alert::error('Unauthorized', 'Only HR, COO, and Super Admin can create CCBRT policies.');
            return redirect()->route('policies.index');
        }

        if ($policyType === 'other_organization' && !$this->canManageOtherOrgPolicies()) {
            Alert::error('Unauthorized', 'Only COO and Super Admin can create Other Organization policies.');
            return redirect()->route('policies.index');
        }

        $divisions = Division::all();
        $departments = Departments::all();
        $policyCategories = PolicyCategory::orderBy('sort_order')->get();

        return view('policies.create', compact('divisions', 'departments', 'policyCategories', 'policyType', 'isHR', 'isCOO'));
    }

    /**
     * Store a newly created policy
     * HR can create CCBRT policies, COO can create both types
     */
    public function store(Request $request)
    {
        $policyType = $request->input('policy_type', 'ccbrt');
        $submissionStatus = $policyType === 'other_organization'
            ? $this->resolveOtherOrganizationSubmissionStatus($request)
            : 'active';

        // Check permissions based on policy type
        if ($policyType === 'ccbrt' && !$this->canManageCCBRTPolicies()) {
            Alert::error('Unauthorized', 'Only HR, COO, and Super Admin can create CCBRT policies.');
            return redirect()->route('policies.index');
        }

        if ($policyType === 'other_organization' && !$this->canManageOtherOrgPolicies()) {
            Alert::error('Unauthorized', 'Only COO and Super Admin can create Other Organization policies.');
            return redirect()->route('policies.index');
        }

        // CCBRT policies: Force text content, no division/department needed
        if ($policyType === 'ccbrt') {
            $isGlobal = true;
            // Force content type to text for CCBRT policies (if not set or disabled, default to text)
            if (!$request->has('content_type') || $request->content_type === '' || $request->content_type === null) {
                $request->merge(['content_type' => 'text']);
            }
            // Force content type to text for CCBRT policies
            if ($request->content_type !== 'text') {
                Alert::error('Error', 'CCBRT policies must be text-based for display in registration and profile.');
                return back()->withInput();
            }
        } else {
            // Other Organization policies: "Apply to all entities" means visible to every user (division_id null, is_global true)
            $applyToAllEntities = $request->has('apply_to_all_entities') && $request->apply_to_all_entities == 1;
            if ($applyToAllEntities) {
                $request->merge(['division_id' => null, 'division_ids' => [], 'is_global' => 1, 'departments' => []]);
            } else {
                $divisionIds = $request->filled('division_ids') && is_array($request->division_ids)
                    ? array_values(array_map('intval', array_filter($request->division_ids)))
                    : ($request->filled('division_id') ? [(int) $request->division_id] : []);
                $request->merge(['division_ids' => $divisionIds]);
                $request->merge(['division_id' => count($divisionIds) === 1 ? ($divisionIds[0] ?? null) : ($divisionIds[0] ?? null)]);
            }
            $isGlobal = $request->has('is_global') && $request->is_global == 1;
        }

        $validated = $request->validate([
            'policy_type' => 'required|in:ccbrt,other_organization',
            'title' => 'required|string|max:255',
            'document_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'division_id' => 'nullable|exists:divisions,id',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
            'content_type' => 'required|in:text,pdf',
            'content' => 'required_if:content_type,text|nullable|string',
            'pdf' => 'required_if:content_type,pdf|file|mimes:pdf|max:10240',
            'departments' => ($policyType === 'ccbrt') ? 'nullable|array' : 'nullable|required_without:is_global|array',
            'departments.*' => 'exists:departments,id',
            'is_global' => 'nullable|boolean',
            'apply_to_all_entities' => 'nullable|boolean',
            'organization_code' => 'nullable|string|max:50',
            'policy_category_id' => 'nullable|exists:policy_categories,id',
            'effective_date' => 'nullable|date',
            'next_review_date' => 'nullable|date',
        ]);

        // Validate division selection for other_organization policies
        if ($policyType === 'other_organization') {
            $hasApplyAll = $request->has('apply_to_all_entities') && $request->input('apply_to_all_entities') == 1;
            $hasDivisions = !empty($request->input('division_ids', []));
            $hasDivision = $request->filled('division_id');
            if (!$hasApplyAll && !$hasDivisions && !$hasDivision) {
                return back()->withErrors(['division_ids' => ['Select at least one entity/division or check Apply to all entities.']])->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $contentType = $request->content_type;
            $content = null;
            $pdfPath = null;

            // Handle content based on type
            if ($contentType === 'text') {
                $content = $request->content;
                // Trim and validate content is not empty
                if (empty(trim(strip_tags($content)))) {
                    Alert::error('Error', 'Policy content cannot be empty. Please enter the policy content.');
                    return back()->withInput();
                }
                $pdfPath = null;
            } else {
                // Handle PDF upload (only for other_organization policies)
                if ($policyType === 'ccbrt') {
                    Alert::error('Error', 'CCBRT policies must be text-based.');
                    return back()->withInput();
                }
                $file = $request->file('pdf');
                $originalName = basename($file->getClientOriginalName());
                $pdfPath = $file->storeAs('policies', $originalName, 'public');
                $content = null; // PDF policies don't need text content
            }

            // Generate document code if not provided
            $divisionIds = $request->division_ids ?? ($request->filled('division_id') ? [(int) $request->division_id] : []);
            $divisionId = ($policyType === 'ccbrt' || !empty($request->apply_to_all_entities)) ? null : (count($divisionIds) > 0 ? $divisionIds[0] : $request->division_id);

            if ($policyType === 'ccbrt') {
                // Create HR policy in policies table
                $documentCode = $request->filled('document_code') ? $request->document_code : null;
                $policy = Policy::create([
                    'title' => $request->title,
                    'document_code' => $documentCode,
                    'description' => $request->description,
                    'division_id' => null, // CCBRT policies don't have division
                    'content' => $content,
                    'content_type' => $contentType,
                    'pdf_path' => $pdfPath,
                    'policy_category_id' => $request->policy_category_id,
                    'is_global' => $isGlobal,
                    'status' => 'active',
                    'created_by' => auth()->id(),
                ]);
            } else {
                // Create COO policy in other_organization_policies table (division_id = first when single, null when multiple)
                $policyDivisionId = count($divisionIds) === 1 ? ($divisionIds[0] ?? null) : null;
                $documentCode = $request->filled('document_code') ? $request->document_code : null;
                $policy = OtherOrganizationPolicy::create([
                    'title' => $request->title,
                    'document_code' => $documentCode,
                    'organization_code' => $request->filled('organization_code') ? $request->organization_code : null,
                    'description' => $request->description,
                    'division_id' => $policyDivisionId,
                    'content' => $content,
                    'content_type' => $contentType,
                    'pdf_path' => $pdfPath,
                    'policy_category_id' => $request->policy_category_id,
                    'is_global' => $isGlobal,
                    'status' => $submissionStatus,
                    'effective_date' => $request->filled('effective_date') ? $request->effective_date : null,
                    'next_review_date' => $request->filled('next_review_date') ? $request->next_review_date : null,
                    'created_by' => auth()->id(),
                ]);
                $policy->divisions()->sync($divisionIds);
            }

            // If not global, attach departments
            if (!$isGlobal && isset($validated['departments'])) {
                foreach ($validated['departments'] as $departmentId) {
                    $policy->departments()->attach($departmentId, [
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            // Send email to all staff in selected entity/departments or all users (organization policies only)
            $recipientCount = 0;
            $organizationEmailsEnabled = $policyType !== 'other_organization' || $this->areOrganizationPolicyEmailsEnabled();
            if ($policyType === 'other_organization' && $submissionStatus === 'active' && $organizationEmailsEnabled) {
                $recipientCount = $this->sendOrganizationPolicyNotifications($policy, true);
            }

            if ($policyType === 'other_organization' && $submissionStatus === 'draft') {
                Alert::success('Success', 'Policy saved as draft. It is not visible to users and no notifications were sent.');
            } elseif ($policyType === 'other_organization' && !$organizationEmailsEnabled) {
                Alert::success('Success', 'Policy created successfully. Organization policy email notifications are currently disabled.');
            } elseif ($policyType === 'other_organization' && $recipientCount > 0) {
                Alert::success('Success', 'Policy created successfully. Notifications have been sent.');
            } else {
                Alert::success('Success', 'Policy created successfully.');
            }
            return redirect()->route('policies.index', $this->policyIndexRouteParams($policyType, $submissionStatus));
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Failed to create policy: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Get users who should receive organization policy notification:
     * - All users (if is_global / "All users" selected)
     * - Users in selected departments (if specific departments selected)
     * - Users in entity/division (if entity selected but no specific departments)
     */
    private function getRecipientsForOrganizationPolicy(OtherOrganizationPolicy $policy)
    {
        $baseQuery = User::whereNotNull('email')->where('email', '!=', '');
        if ($policy->is_global) {
            return $baseQuery->get()->unique('id');
        }
        if ($policy->departments && $policy->departments->isNotEmpty()) {
            $deptIds = $policy->departments->pluck('id')->toArray();
            return $baseQuery->whereIn('deptId', $deptIds)->get()->unique('id');
        }
        // Single division (legacy) or multiple divisions (pivot)
        if ($policy->divisions && $policy->divisions->isNotEmpty()) {
            $deptIds = [];
            foreach ($policy->divisions as $division) {
                $deptIds = array_merge($deptIds, $division->departments()->pluck('departments.id')->toArray());
            }
            $deptIds = array_unique($deptIds);
            return !empty($deptIds) ? $baseQuery->whereIn('deptId', $deptIds)->get()->unique('id') : collect();
        }
        if ($policy->division_id && $policy->division) {
            $deptIds = $policy->division->departments()->pluck('departments.id')->toArray();
            return $baseQuery->whereIn('deptId', $deptIds)->get()->unique('id');
        }
        return collect();
    }

    /**
     * Display the specified policy
     */
    public function show($id)
    {
        // Determine which table to use
        $policyType = request()->get('type', 'ccbrt');

        if ($policyType === 'ccbrt') {
            $policy = Policy::findOrFail($id);
        } else {
            $policy = OtherOrganizationPolicy::findOrFail($id);
        }

        // Non-managers can only view active policies
        if (!$this->canManagePolicies() && $policy->status !== 'active') {
            Alert::error('Unauthorized', 'This policy is not available.');
            return redirect()->route('policies.index');
        }

        $policyType = request()->get('type', 'ccbrt');
        return view('policies.show', compact('policy', 'policyType'));
    }

    /**
     * Show the form for editing the specified policy
     * HR can edit CCBRT policies, COO can only edit Other Organization policies
     */
    public function edit($id)
    {
        $user = auth()->user();
        $isCOO = $user->hasRole('coo') && !$user->hasRole('super-admin');

        // Determine which table to use based on request
        $policyType = request()->get('type', 'ccbrt');

        if ($policyType === 'ccbrt') {
            $policy = Policy::findOrFail($id);
            // Prevent COO from editing CCBRT policies
            if ($isCOO) {
                Alert::error('Unauthorized', 'COO cannot edit CCBRT policies. Only HR and Super Admin can edit CCBRT policies.');
                return redirect()->route('policies.index');
            }
            if (!$this->canManageCCBRTPolicies()) {
                Alert::error('Unauthorized', 'Only HR, COO, and Super Admin can edit CCBRT policies.');
                return redirect()->route('policies.index');
            }
        } else {
            $policy = OtherOrganizationPolicy::with('divisions')->findOrFail($id);
            if (!$this->canManageOtherOrgPolicies()) {
                Alert::error('Unauthorized', 'Only COO and Super Admin can edit Other Organization policies.');
                return redirect()->route('policies.index');
            }
        }

        $divisions = Division::all();
        $departments = Departments::all();
        $policyCategories = PolicyCategory::orderBy('sort_order')->get();

        // Pass policy type to view
        return view('policies.edit', compact('policy', 'divisions', 'departments', 'policyCategories', 'policyType'));
    }

    /**
     * Update the specified policy
     * HR can update CCBRT policies, COO can update both types
     */
    public function update(Request $request, $id)
    {
        // Determine which table to use - check query parameter first, then request input
        $policyType = $request->get('type', $request->input('policy_type', 'ccbrt'));

        if ($policyType === 'ccbrt') {
            $policy = Policy::findOrFail($id);
            if (!$this->canManageCCBRTPolicies()) {
                Alert::error('Unauthorized', 'Only HR, COO, and Super Admin can update CCBRT policies.');
                return redirect()->route('policies.index');
            }
        } else {
            $policy = OtherOrganizationPolicy::findOrFail($id);
            if (!$this->canManageOtherOrgPolicies()) {
                Alert::error('Unauthorized', 'Only COO and Super Admin can update Other Organization policies.');
                return redirect()->route('policies.index');
            }
        }

        $submissionStatus = $policyType === 'other_organization'
            ? $this->resolveOtherOrganizationSubmissionStatus($request, $policy->status)
            : 'active';

        if ($policyType === 'other_organization' && $request->has('apply_to_all_entities') && $request->apply_to_all_entities == 1) {
            $request->merge(['division_id' => null, 'division_ids' => [], 'is_global' => 1, 'departments' => []]);
        } elseif ($policyType === 'other_organization') {
            $divisionIds = $request->filled('division_ids') && is_array($request->division_ids)
                ? array_values(array_map('intval', array_filter($request->division_ids)))
                : ($request->filled('division_id') ? [(int) $request->division_id] : []);
            $request->merge(['division_ids' => $divisionIds]);
            $request->merge(['division_id' => count($divisionIds) === 1 ? ($divisionIds[0] ?? null) : ($divisionIds[0] ?? null)]);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'document_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'division_id' => 'nullable|exists:divisions,id',
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
            'content_type' => 'required|in:text,pdf',
            'content' => 'required_if:content_type,text|nullable|string',
            'pdf' => 'nullable|file|mimes:pdf|max:10240',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'is_global' => 'nullable|boolean',
            'apply_to_all_entities' => 'nullable|boolean',
            'organization_code' => 'nullable|string|max:50',
            'policy_category_id' => 'nullable|exists:policy_categories,id',
            'effective_date' => 'nullable|date',
            'next_review_date' => 'nullable|date',
        ]);

        if ($policyType === 'other_organization' && !$request->has('apply_to_all_entities') && empty($request->division_ids) && !$request->filled('division_id')) {
            return back()->withErrors(['division_ids' => ['Select at least one entity/division or check Apply to all entities.']])->withInput();
        }

        DB::beginTransaction();
        try {
            $isGlobal = $request->has('is_global') && $request->is_global == 1;
            $contentType = $request->content_type;
            $divisionIds = $request->division_ids ?? ($request->filled('division_id') ? [(int) $request->division_id] : []);
            $divisionId = ($policyType === 'ccbrt' || !empty($request->apply_to_all_entities)) ? null : (count($divisionIds) > 0 ? $divisionIds[0] : $request->division_id);
            $policyDivisionId = $policyType === 'other_organization' ? (count($divisionIds) === 1 ? ($divisionIds[0] ?? null) : null) : null;

            $updateData = [
                'title' => $request->title,
                'document_code' => $request->document_code,
                'description' => $request->description,
                'division_id' => $policyType === 'ccbrt' ? null : ($policyType === 'other_organization' ? $policyDivisionId : $divisionId),
                'content_type' => $contentType,
                'policy_category_id' => $request->policy_category_id,
                'is_global' => $isGlobal,
                'updated_by' => auth()->id(),
            ];
            if ($policyType === 'other_organization') {
                $updateData['organization_code'] = $request->filled('organization_code') ? $request->organization_code : null;
                $updateData['status'] = $submissionStatus;
                $updateData['effective_date'] = $request->filled('effective_date') ? $request->effective_date : null;
                $updateData['next_review_date'] = $request->filled('next_review_date') ? $request->next_review_date : null;
            }

            // Handle content based on type
            if ($contentType === 'text') {
                $updateData['content'] = $request->content;
                // If switching from PDF to text, delete old PDF
                if ($policy->content_type === 'pdf' && $policy->pdf_path && Storage::exists('public/' . $policy->pdf_path)) {
                    Storage::delete('public/' . $policy->pdf_path);
                }
                $updateData['pdf_path'] = null;
            } else {
                // Handle PDF upload
                if ($request->hasFile('pdf')) {
                    // Delete old PDF if exists
                    if ($policy->pdf_path && Storage::exists('public/' . $policy->pdf_path)) {
                        Storage::delete('public/' . $policy->pdf_path);
                    }

                    $file = $request->file('pdf');
                    $originalName = basename($file->getClientOriginalName());
                    $pdfPath = $file->storeAs('policies', $originalName, 'public');
                    $updateData['pdf_path'] = $pdfPath;
                } else {
                    // Keep existing PDF if no new file uploaded
                    $updateData['pdf_path'] = $policy->pdf_path;
                }
                // Clear text content if switching to PDF
                if ($policy->content_type === 'text') {
                    $updateData['content'] = null;
                }
            }

            $policy->update($updateData);

            // Sync divisions pivot for other_organization
            if ($policyType === 'other_organization') {
                $policy->divisions()->sync($divisionIds ?? []);
            }

            // Update departments if not global
            if (!$isGlobal && $request->has('departments')) {
                $policy->departments()->sync($request->departments);
            } elseif ($isGlobal) {
                $policy->departments()->detach();
            }

            DB::commit();

            // Send email to staff in selected entity/departments or all users (organization policies only)
            $recipientCount = 0;
            $organizationEmailsEnabled = $policyType !== 'other_organization' || $this->areOrganizationPolicyEmailsEnabled();
            if ($policyType === 'other_organization' && $submissionStatus === 'active' && $organizationEmailsEnabled) {
                $recipientCount = $this->sendOrganizationPolicyNotifications($policy, false);
            }

            if ($policyType === 'other_organization' && $submissionStatus === 'draft') {
                Alert::success('Success', 'Policy saved as draft. It is not visible to users and no notifications were sent.');
            } elseif ($policyType === 'other_organization' && !$organizationEmailsEnabled) {
                Alert::success('Success', 'Policy updated successfully. Organization policy email notifications are currently disabled.');
            } elseif ($policyType === 'other_organization' && $recipientCount > 0) {
                Alert::success('Success', 'Policy updated successfully. Notifications have been sent.');
            } else {
                Alert::success('Success', 'Policy updated successfully.');
            }
            return redirect()->route('policies.index', $this->policyIndexRouteParams($policyType, $submissionStatus));
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Failed to update policy: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified policy from storage
     * Only Super Admin can delete
     */
    public function destroy($id)
    {
        if (!$this->isSuperAdmin()) {
            Alert::error('Unauthorized', 'Only Super Admin can delete policies.');
            return redirect()->route('policies.index');
        }

        // Determine which table to use
        $policyType = request()->get('type', 'ccbrt');

        if ($policyType === 'ccbrt') {
            $policy = Policy::findOrFail($id);
        } else {
            $policy = OtherOrganizationPolicy::findOrFail($id);
        }

        // Delete the PDF file if exists
        if ($policy->pdf_path && Storage::exists('public/' . $policy->pdf_path)) {
            Storage::delete('public/' . $policy->pdf_path);
        }

        $policy->delete();

        Alert::success('Success', 'Policy deleted successfully.');
        return redirect()->route('policies.index');
    }

    /**
     * Get departments by entity/division (AJAX)
     */
    public function getDepartmentsByDivision($divisionId)
    {
        $division = Division::findOrFail($divisionId);
        $departments = $division->departments()->get(['departments.id', 'departments.dept_name']);

        return response()->json($departments);
    }

    /**
     * Record a view for an organization policy (increment view_count). Used when user opens policy in modal.
     */
    public function recordView($id)
    {
        if (request()->get('type') !== 'other_organization') {
            return response()->json(['error' => 'Invalid type'], 400);
        }
        $policy = OtherOrganizationPolicy::findOrFail($id);
        $policy->increment('view_count');
        $policy->refresh();

        return response()->json(['view_count' => $policy->view_count]);
    }

    /**
     * Archive the specified policy
     * HR can archive CCBRT policies, COO can only archive Other Organization policies
     */
    public function archive($id)
    {
        $user = auth()->user();
        $isCOO = $user->hasRole('coo') && !$user->hasRole('super-admin');

        // Determine which table to use
        $policyType = request()->get('type', 'ccbrt');

        if ($policyType === 'ccbrt') {
            $policy = Policy::findOrFail($id);
            // Prevent COO from archiving CCBRT policies
            if ($isCOO) {
                Alert::error('Unauthorized', 'COO cannot archive CCBRT policies. Only HR and Super Admin can archive CCBRT policies.');
                return redirect()->route('policies.index');
            }
            if (!$this->canManageCCBRTPolicies()) {
                Alert::error('Unauthorized', 'Only HR, COO, and Super Admin can archive CCBRT policies.');
                return redirect()->route('policies.index');
            }
        } else {
            $policy = OtherOrganizationPolicy::findOrFail($id);
            if (!$this->canManageOtherOrgPolicies()) {
                Alert::error('Unauthorized', 'Only COO and Super Admin can archive Other Organization policies.');
                return redirect()->route('policies.index');
            }
        }

        $policy->update([
            'status' => 'archived',
            'updated_by' => auth()->id(),
        ]);

        Alert::success('Success', 'Policy archived successfully.');
        return redirect()->route('policies.index', ['type' => $policyType]);
    }

    /**
     * Restore an archived policy
     * HR can restore CCBRT policies, COO can only restore Other Organization policies
     */
    public function restore($id)
    {
        $user = auth()->user();
        $isCOO = $user->hasRole('coo') && !$user->hasRole('super-admin');

        // Determine which table to use
        $policyType = request()->get('type', 'ccbrt');

        if ($policyType === 'ccbrt') {
            $policy = Policy::findOrFail($id);
            // Prevent COO from restoring CCBRT policies
            if ($isCOO) {
                Alert::error('Unauthorized', 'COO cannot restore CCBRT policies. Only HR and Super Admin can restore CCBRT policies.');
                return redirect()->route('policies.index');
            }
            if (!$this->canManageCCBRTPolicies()) {
                Alert::error('Unauthorized', 'Only HR, COO, and Super Admin can restore CCBRT policies.');
                return redirect()->route('policies.index');
            }
        } else {
            $policy = OtherOrganizationPolicy::findOrFail($id);
            if (!$this->canManageOtherOrgPolicies()) {
                Alert::error('Unauthorized', 'Only COO and Super Admin can restore Other Organization policies.');
                return redirect()->route('policies.index');
            }
        }

        $policy->update([
            'status' => 'active',
            'updated_by' => auth()->id(),
        ]);

        Alert::success('Success', 'Policy restored successfully.');
        return redirect()->route('policies.index', ['type' => $policyType]);
    }

    // Legacy methods for backward compatibility
    public function user()
    {
        $user = auth()->user();
        // Show all HR policies (policies table) - no filtering
        $policies = Policy::active()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('policies.user', compact('policies', 'user'));
    }

    public function accept(Request $request)
    {
        $request->user()->update(['accepted_policies' => true]);
        return redirect()->route('home');
    }

    public function downloadPolicy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'policy_id' => 'required|integer|exists:policies,id',
        ]);

        $policy = Policy::findOrFail($request->input('policy_id'));

        if ($policy->content_type === 'pdf' && $policy->pdf_path) {
            return Storage::download('public/' . $policy->pdf_path);
        }

        // Generate PDF from text content
        $pdf = PDF::loadView('pdf.index', [
            'policy' => $policy,
            'user' => $user,
        ]);

        return $pdf->download($policy->title . '.pdf');
    }

    public function downloadMultiplePolicies(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'policy_ids' => 'required|string',
        ]);

        $policyIds = explode(',', $request->input('policy_ids'));
        $policies = Policy::whereIn('id', $policyIds)->get();

        if ($policies->isEmpty()) {
            return redirect()->back()->with('error', 'No policies found to download.');
        }

        $signaturePath = null;
        if ($user->signature) {
            try {
                $signatureData = $user->signature;
                if (strpos($signatureData, 'data:image') !== false) {
                    $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
                }
                $decodedSignature = base64_decode($signatureData);
                if ($decodedSignature !== false) {
                    $tempPath = storage_path('app/temp/signature_' . $user->id . '_' . time() . '.png');
                    if (!file_exists(storage_path('app/temp'))) {
                        mkdir(storage_path('app/temp'), 0755, true);
                    }
                    file_put_contents($tempPath, $decodedSignature);
                    $signaturePath = $tempPath;
                }
            } catch (\Exception $e) {
                \Log::error('Error processing signature: ' . $e->getMessage());
            }
        }

        $pdf = PDF::loadView('pdf.multiple-policies', [
            'policies' => $policies,
            'user' => $user,
            'signaturePath' => $signaturePath,
        ])->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'chroot' => realpath(base_path()),
        ]);

        $filename = 'CCBRT_Policies_' . $user->ccbrt_code . '_' . date('Y-m-d') . '.pdf';

        $response = $pdf->download($filename);

        if ($signaturePath && file_exists($signaturePath)) {
            register_shutdown_function(function () use ($signaturePath) {
                @unlink($signaturePath);
            });
        }

        return $response;
    }

    public function previewMultiplePolicies(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $request->validate([
            'policy_ids' => 'required|string',
        ]);

        $policyIds = explode(',', $request->input('policy_ids'));
        $policies = Policy::whereIn('id', $policyIds)->get();

        return view('pdf.preview-policies', compact('policies', 'user'));
    }

    // Legacy methods - kept for backward compatibility but deprecated
    public function createDepartmentPolicy()
    {
        if (!$this->canManagePolicies()) {
            Alert::error('Unauthorized', 'Only COO and Super Admin can create policies.');
            return redirect()->route('policies.index');
        }
        return redirect()->route('policies.create');
    }

    public function storeDepartmentPolicy(Request $request)
    {
        return $this->store($request);
    }

    public function editDepartmentPolicy($id)
    {
        $policy = Policy::findOrFail($id);
        return $this->edit($policy);
    }

    public function updateDepartmentPolicy(Request $request, $id)
    {
        $policy = Policy::findOrFail($id);
        return $this->update($request, $policy);
    }

    public function destroydept($id)
    {
        $policy = Policy::findOrFail($id);
        return $this->destroy($policy);
    }

    public function index1()
    {
        return $this->index(request());
    }
}
