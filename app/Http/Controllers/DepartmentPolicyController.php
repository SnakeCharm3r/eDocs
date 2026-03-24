<?php

namespace App\Http\Controllers;

use App\Models\DepartmentPolicy;
use App\Models\Departments;
use App\Models\User;
use App\Mail\DepartmentPolicyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class DepartmentPolicyController extends Controller
{
    /** Max file size 5 MB in KB */
    private const PDF_MAX_SIZE_KB = 5120;

    /**
     * Get department IDs the current user can manage (line manager: their dept(s); super-admin: all).
     */
    private function allowedDepartmentIdsForManage(): array
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            return Departments::pluck('id')->all();
        }
        if ($user->hasRole('line-manager')) {
            $managed = method_exists($user, 'managedDepartments')
                ? $user->managedDepartments()->pluck('departments.id')->all()
                : [];
            $own = $user->deptId ? [(int) $user->deptId] : [];
            return array_values(array_unique(array_filter(array_merge($managed, $own))));
        }
        return [];
    }

    /**
     * Check if user can manage (create/edit/delete) department policies for the given department.
     */
    private function canManageDepartment(int $departmentId): bool
    {
        $user = auth()->user();
        if ($user->hasRole('super-admin')) {
            return true;
        }
        if ($user->hasRole('line-manager')) {
            return in_array($departmentId, $this->allowedDepartmentIdsForManage(), true);
        }
        return false;
    }

    /**
     * Index: staff see policies for their department + visible_to_all_staff; line managers see/manage their dept policies.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = DepartmentPolicy::with(['department', 'creator'])->where('status', 'active');

        if (!$user->hasRole('super-admin')) {
            $deptId = $user->deptId ? (int) $user->deptId : null;
            $managerDeptIds = $this->allowedDepartmentIdsForManage();
            $canManageAny = !empty($managerDeptIds);

            if ($canManageAny) {
                // Line manager: see policies from their managed departments
                $query->whereIn('department_id', $managerDeptIds);
            } else {
                // Staff: see policies for their department OR visible to all staff
                $query->where(function ($q) use ($deptId) {
                    $q->where('visible_to_all_staff', true);
                    if ($deptId) {
                        $q->orWhere('department_id', $deptId);
                    }
                });
            }
        }

        $policies = $query->orderBy('created_at', 'desc')->get();
        $departments = $user->hasRole('super-admin')
            ? Departments::orderBy('dept_name')->get()
            : Departments::whereIn('id', $this->allowedDepartmentIdsForManage())->orderBy('dept_name')->get();
        $canManage = !empty($this->allowedDepartmentIdsForManage());

        return view('department-policies.index', compact('policies', 'departments', 'canManage'));
    }

    /**
     * Create form: only line managers (their depts) or super admin.
     */
    public function create()
    {
        $allowedIds = $this->allowedDepartmentIdsForManage();
        if (empty($allowedIds)) {
            Alert::error('Unauthorized', 'Only Line Managers (for their department) or Super Admin can add department policies.');
            return redirect()->route('department-policies.index');
        }
        $departments = Departments::whereIn('id', $allowedIds)->orderBy('dept_name')->get();
        return view('department-policies.create', compact('departments'));
    }

    /**
     * Store: PDF only, max 5 MB; then send email to department staff or all staff.
     */
    public function store(Request $request)
    {
        $allowedIds = $this->allowedDepartmentIdsForManage();
        if (empty($allowedIds)) {
            Alert::error('Unauthorized', 'Only Line Managers or Super Admin can add department policies.');
            return redirect()->route('department-policies.index');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'document_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'department_id' => 'required|exists:departments,id|in:' . implode(',', $allowedIds),
            'visible_to_all_staff' => 'nullable|boolean',
            'pdf' => 'required|file|mimes:pdf|max:' . self::PDF_MAX_SIZE_KB,
        ], [
            'pdf.required' => 'Please upload a PDF document.',
            'pdf.mimes' => 'Only PDF documents are allowed.',
            'pdf.max' => 'The PDF must not be larger than 5 MB.',
        ]);

        $visibleToAll = $request->boolean('visible_to_all_staff');

        DB::beginTransaction();
        try {
            $file = $request->file('pdf');
            $originalName = basename($file->getClientOriginalName());
            $path = $file->storeAs('department-policies', $originalName, 'public');

            $policy = DepartmentPolicy::create([
                'title' => $validated['title'],
                'document_code' => $validated['document_code'] ?? null,
                'description' => $validated['description'] ?? null,
                'pdf_path' => $path,
                'department_id' => $validated['department_id'],
                'visible_to_all_staff' => $visibleToAll,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            // Send email to department staff or all staff (only users with email)
            $recipients = $visibleToAll
                ? User::whereNotNull('email')->where('email', '!=', '')->get()
                : User::where('deptId', $policy->department_id)->whereNotNull('email')->where('email', '!=', '')->get();

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->queue(new DepartmentPolicyNotification($policy, $recipient, true));
                } catch (\Throwable $e) {
                    // Log but don't fail the request
                    report($e);
                }
            }

            Alert::success('Success', 'Department policy created and notifications sent.');
            return redirect()->route('department-policies.index');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($path) && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
            Alert::error('Error', 'Failed to create policy: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show single policy (view PDF or info).
     */
    public function show($id)
    {
        $policy = DepartmentPolicy::with(['department', 'creator'])->findOrFail($id);
        $user = auth()->user();

        if (!$user->hasRole('super-admin')) {
            $managerDeptIds = $this->allowedDepartmentIdsForManage();
            $canManage = in_array($policy->department_id, $managerDeptIds, true);
            $canView = $canManage
                || $policy->visible_to_all_staff
                || ($user->deptId && (int) $user->deptId === $policy->department_id);
            if (!$canView) {
                Alert::error('Unauthorized', 'You cannot view this policy.');
                return redirect()->route('department-policies.index');
            }
        }

        // Increment view count (how many times this policy was viewed)
        $policy->increment('view_count');
        $policy->refresh();

        return view('department-policies.show', compact('policy'));
    }

    /**
     * Edit form.
     */
    public function edit($id)
    {
        $policy = DepartmentPolicy::findOrFail($id);
        if (!$this->canManageDepartment($policy->department_id)) {
            Alert::error('Unauthorized', 'You can only edit policies for your managed department(s).');
            return redirect()->route('department-policies.index');
        }
        $allowedIds = $this->allowedDepartmentIdsForManage();
        $departments = Departments::whereIn('id', $allowedIds)->orderBy('dept_name')->get();
        return view('department-policies.edit', compact('policy', 'departments'));
    }

    /**
     * Update: PDF optional; if provided, PDF only max 5 MB.
     */
    public function update(Request $request, $id)
    {
        $policy = DepartmentPolicy::findOrFail($id);
        if (!$this->canManageDepartment($policy->department_id)) {
            Alert::error('Unauthorized', 'You can only update policies for your managed department(s).');
            return redirect()->route('department-policies.index');
        }

        $rules = [
            'title' => 'required|string|max:255',
            'document_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'visible_to_all_staff' => 'nullable|boolean',
        ];
        if ($request->hasFile('pdf')) {
            $rules['pdf'] = 'required|file|mimes:pdf|max:' . self::PDF_MAX_SIZE_KB;
        }
        $validated = $request->validate($rules, [
            'pdf.mimes' => 'Only PDF documents are allowed.',
            'pdf.max' => 'The PDF must not be larger than 5 MB.',
        ]);

        DB::beginTransaction();
        try {
            $policy->title = $validated['title'];
            $policy->document_code = $validated['document_code'] ?? null;
            $policy->description = $validated['description'] ?? null;
            $policy->visible_to_all_staff = $request->boolean('visible_to_all_staff');
            $policy->updated_by = auth()->id();

            if ($request->hasFile('pdf')) {
                $oldPath = $policy->pdf_path;
                $originalName = basename($request->file('pdf')->getClientOriginalName());
                $policy->pdf_path = $request->file('pdf')->storeAs('department-policies', $originalName, 'public');
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $policy->save();
            DB::commit();

            // Send email (queued) to department staff or all staff when policy is updated
            $recipients = $policy->visible_to_all_staff
                ? User::whereNotNull('email')->where('email', '!=', '')->get()
                : User::where('deptId', $policy->department_id)->whereNotNull('email')->where('email', '!=', '')->get();

            foreach ($recipients as $recipient) {
                try {
                    Mail::to($recipient->email)->queue(new DepartmentPolicyNotification($policy, $recipient, false));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            Alert::success('Success', 'Department policy updated.');
            return redirect()->route('department-policies.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'Failed to update policy: ' . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Destroy.
     */
    public function destroy($id)
    {
        $policy = DepartmentPolicy::findOrFail($id);
        if (!$this->canManageDepartment($policy->department_id)) {
            Alert::error('Unauthorized', 'You can only delete policies for your managed department(s).');
            return redirect()->route('department-policies.index');
        }
        $path = $policy->pdf_path;
        $policy->delete();
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        Alert::success('Success', 'Department policy deleted.');
        return redirect()->route('department-policies.index');
    }
}
