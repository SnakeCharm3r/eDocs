<?php

namespace App\Http\Controllers;

use App\Models\Departments;
use App\Models\Goal;
use App\Models\GoalAlignment;
use App\Models\GoalCycle;
use App\Models\GoalKpi;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class GoalSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function managedDepartmentIdsForHecUser($user): array
    {
        $deptIdsByMember = DB::table('departments')
            ->where('hec_member_id', $user->id)
            ->pluck('id')
            ->toArray();

        $userRoleName = strtolower($user->getRoleNames()->first() ?? '');
        $deptIdsByHecLevel = [];
        if (!empty($userRoleName)) {
            $deptIdsByHecLevel = DB::table('departments')
                ->join('hecs', 'departments.hec_id', '=', 'hecs.id')
                ->whereRaw('LOWER(hecs.hec_level_name) = ?', [$userRoleName])
                ->pluck('departments.id')
                ->toArray();
        }

        return array_values(array_unique(array_merge($deptIdsByMember, $deptIdsByHecLevel)));
    }

    private function canDeleteGoal(Goal $goal, $user): bool
    {
        if ($goal->delete_status != 0) {
            return false;
        }

        $isHec = $user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro', 'hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccdro', 'hec-ccd']);
        $isLineManager = $user->hasRole('line-manager');

        if (!$isHec && !$isLineManager) {
            return false;
        }

        if ($goal->level === 'hec') {
            return $isHec;
        }

        if (empty($goal->department_id)) {
            return false;
        }

        if ($isLineManager) {
            return (string) $goal->department_id === (string) $user->deptId;
        }

        $deptIds = $this->managedDepartmentIdsForHecUser($user);
        return in_array((int) $goal->department_id, array_map('intval', $deptIds), true);
    }

    public function dashboard()
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['coo', 'cfo', 'cms', 'ccdro', 'hec-cfo', 'hec-coo', 'hec-cms', 'hec-ccdro', 'hec-ccd'])) {
            return redirect()->route('goal-setting.hec.inbox');
        }

        if ($user->hasRole('line-manager')) {
            return redirect()->route('goal-setting.line-manager.inbox');
        }

        return redirect()->route('goal-setting.staff.my-goals');
    }

    public function hecInbox(Request $request)
    {
        $user = auth()->user();
        $deptIds = $this->managedDepartmentIdsForHecUser($user);

        $cycleId = $request->query('cycle');

        $query = Goal::with(['cycle', 'department', 'owner'])
            ->where('delete_status', 0)
            ->whereIn('status', ['submitted_to_hec'])
            ->whereNotNull('department_id');

        if (!empty($deptIds)) {
            $query->whereIn('department_id', $deptIds);
        } else {
            $query->whereRaw('1 = 0');
        }

        if (!empty($cycleId)) {
            $query->where('goal_cycle_id', $cycleId);
        }

        $goals = $query->orderBy('updated_at', 'desc')->get();
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();

        return view('goal-setting.hec.inbox', compact('goals', 'cycles', 'cycleId'));
    }

    public function lineManagerInbox(Request $request)
    {
        $user = auth()->user();
        $deptId = $user->deptId;
        $cycleId = $request->query('cycle');

        $query = Goal::with(['cycle', 'department', 'owner'])
            ->where('delete_status', 0)
            ->where('department_id', $deptId)
            ->whereIn('status', ['submitted_to_line_manager']);

        if (!empty($cycleId)) {
            $query->where('goal_cycle_id', $cycleId);
        }

        $goals = $query->orderBy('updated_at', 'desc')->get();
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();

        return view('goal-setting.line-manager.inbox', compact('goals', 'cycles', 'cycleId'));
    }

    public function lineManagerDepartmentGoals(Request $request)
    {
        $user = auth()->user();
        $deptId = $user->deptId;
        $cycleId = $request->query('cycle');

        $query = Goal::with(['cycle', 'department'])
            ->where('delete_status', 0)
            ->where('level', 'department')
            ->where('department_id', $deptId)
            ->orderBy('updated_at', 'desc');

        if (!empty($cycleId)) {
            $query->where('goal_cycle_id', $cycleId);
        }

        $goals = $query->get();
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();

        return view('goal-setting.line-manager.department-goals', compact('goals', 'cycles', 'cycleId'));
    }

    public function myGoals(Request $request)
    {
        $user = auth()->user();
        $cycleId = $request->query('cycle');

        $query = Goal::with(['cycle', 'department', 'unit'])
            ->where('delete_status', 0)
            ->where('owner_user_id', $user->id)
            ->orderBy('updated_at', 'desc');

        if (!empty($cycleId)) {
            $query->where('goal_cycle_id', $cycleId);
        }

        $goals = $query->get();
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();

        return view('goal-setting.staff.my-goals', compact('goals', 'cycles', 'cycleId'));
    }

    public function staffGoalsCreate(Request $request)
    {
        $user = auth()->user();
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();
        $selectedCycleId = $request->query('cycle');
        $units = Unit::orderBy('name', 'asc')->get();

        return view('goal-setting.staff.create', compact('cycles', 'selectedCycleId', 'units', 'user'));
    }

    public function goalEdit($id)
    {
        $goal = Goal::where('id', $id)->where('delete_status', 0)->firstOrFail();
        return view('goal-setting.goals.edit', compact('goal'));
    }

    public function goalUpdate(Request $request, $id)
    {
        $goal = Goal::where('id', $id)->where('delete_status', 0)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $goal->update([
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Goal updated');
        return redirect()->back();
    }

    public function submitToLineManager($goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $goal->update([
            'status' => 'submitted_to_line_manager',
            'submitted_to_line_manager_at' => now(),
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Submitted to Line Manager');
        return redirect()->back();
    }

    public function approveByLineManager($goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $goal->update([
            'line_manager_approved_by' => auth()->id(),
            'line_manager_approved_at' => now(),
            'status' => $goal->hec_approved_at ? 'approved' : 'line_manager_approved',
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Approved by Line Manager');
        return redirect()->back();
    }

    public function submitToHec($goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $goal->update([
            'status' => 'submitted_to_hec',
            'submitted_to_hec_at' => now(),
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Submitted to HEC');
        return redirect()->back();
    }

    public function approveByHec($goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $goal->update([
            'hec_approved_by' => auth()->id(),
            'hec_approved_at' => now(),
            'status' => $goal->line_manager_approved_at ? 'approved' : 'hec_approved',
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Approved by HEC');
        return redirect()->back();
    }

    public function rejectGoal(Request $request, $goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $goal->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Goal rejected');
        return redirect()->back();
    }

    public function hecGoalsCreate(Request $request)
    {
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();
        $selectedCycleId = $request->query('cycle');

        return view('goal-setting.hec-goals.create', compact('cycles', 'selectedCycleId'));
    }

    public function hecGoalsIndex(Request $request)
    {
        $cycleId = $request->query('cycle');

        $query = Goal::with(['cycle'])
            ->where('delete_status', 0)
            ->where('level', 'hec')
            ->orderBy('updated_at', 'desc');

        if (!empty($cycleId)) {
            $query->where('goal_cycle_id', $cycleId);
        }

        $goals = $query->get();
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();

        return view('goal-setting.hec-goals.index', compact('goals', 'cycles', 'cycleId'));
    }

    public function destroyGoal($goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();
        $user = auth()->user();

        if (!$this->canDeleteGoal($goal, $user)) {
            abort(403);
        }

        $goal->update([
            'delete_status' => 1,
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Goal deleted');
        return redirect()->back();
    }

    public function hecGoalsAssignDepartments($goalId)
    {
        $goal = Goal::with('cycle')
            ->where('id', $goalId)
            ->where('delete_status', 0)
            ->firstOrFail();

        $departments = Departments::where(function ($query) {
            $query->where('delete_status', '!=', '1')
                ->orWhereNull('delete_status');
        })
            ->orderBy('dept_name', 'asc')
            ->get();

        return view('goal-setting.hec-goals.assign-departments', compact('goal', 'departments'));
    }

    public function cyclesIndex()
    {
        $cycles = GoalCycle::orderBy('created_at', 'desc')->get();
        if (view()->exists('goal-setting.cycles.index')) {
            return view('goal-setting.cycles.index', compact('cycles'));
        }

        return response()->json([
            'status' => 200,
            'data' => $cycles,
        ]);
    }

    public function cyclesCreate()
    {
        if (view()->exists('goal-setting.cycles.create')) {
            return view('goal-setting.cycles.create');
        }

        return response()->json([
            'status' => 200,
            'message' => 'OK',
        ]);
    }

    public function cyclesStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:goal_cycles,name',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:draft,active,closed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        GoalCycle::create([
            'name' => $request->input('name'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status'),
            'createdBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Goal cycle created');
        return redirect()->route('goal-setting.cycles.index');
    }

    public function storeHecGoal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'goal_cycle_id' => 'required|exists:goal_cycles,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'kpis' => 'nullable|array',
            'kpis.*.name' => 'required_with:kpis|string|max:255',
            'kpis.*.target' => 'nullable|string|max:255',
            'kpis.*.unit' => 'nullable|string|max:50',
            'kpis.*.weight' => 'nullable|numeric',
            'kpis.*.baseline' => 'nullable|string|max:255',
            'kpis.*.due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $goal = Goal::create([
            'goal_cycle_id' => $request->input('goal_cycle_id'),
            'parent_goal_id' => null,
            'level' => 'hec',
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'status' => 'draft',
            'createdBy' => auth()->id(),
            'delete_status' => 0,
        ]);

        foreach (($request->input('kpis') ?? []) as $kpi) {
            GoalKpi::create([
                'goal_id' => $goal->id,
                'name' => $kpi['name'] ?? null,
                'target' => $kpi['target'] ?? null,
                'unit' => $kpi['unit'] ?? null,
                'weight' => $kpi['weight'] ?? null,
                'baseline' => $kpi['baseline'] ?? null,
                'due_date' => $kpi['due_date'] ?? null,
                'createdBy' => auth()->id(),
                'delete_status' => 0,
            ]);
        }

        Alert::success('successful', 'HEC goal created');
        return redirect()->back();
    }

    public function assignHecGoalToDepartments(Request $request, $goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'department_ids' => 'required|array|min:1',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        foreach ($request->input('department_ids') as $departmentId) {
            Goal::firstOrCreate([
                'goal_cycle_id' => $goal->goal_cycle_id,
                'parent_goal_id' => $goal->id,
                'level' => 'department',
                'department_id' => $departmentId,
                'title' => $goal->title,
            ], [
                'description' => $goal->description,
                'status' => 'draft',
                'createdBy' => auth()->id(),
                'delete_status' => 0,
            ]);
        }

        Alert::success('successful', 'Assigned to departments');
        return redirect()->back();
    }

    public function storeDepartmentGoal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'goal_cycle_id' => 'required|exists:goal_cycles,id',
            'department_id' => 'required|exists:departments,id',
            'parent_goal_id' => 'nullable|exists:goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Goal::create([
            'goal_cycle_id' => $request->input('goal_cycle_id'),
            'parent_goal_id' => $request->input('parent_goal_id'),
            'level' => 'department',
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'department_id' => $request->input('department_id'),
            'status' => 'draft',
            'createdBy' => auth()->id(),
            'delete_status' => 0,
        ]);

        Alert::success('successful', 'Department goal created');
        return redirect()->back();
    }

    public function storeUnitGoal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'goal_cycle_id' => 'required|exists:goal_cycles,id',
            'department_id' => 'required|exists:departments,id',
            'unit_id' => 'required|exists:units,id',
            'parent_goal_id' => 'nullable|exists:goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Goal::create([
            'goal_cycle_id' => $request->input('goal_cycle_id'),
            'parent_goal_id' => $request->input('parent_goal_id'),
            'level' => 'unit',
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'department_id' => $request->input('department_id'),
            'unit_id' => $request->input('unit_id'),
            'status' => 'draft',
            'createdBy' => auth()->id(),
            'delete_status' => 0,
        ]);

        Alert::success('successful', 'Unit goal created');
        return redirect()->back();
    }

    public function storeStaffGoal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'goal_cycle_id' => 'required|exists:goal_cycles,id',
            'department_id' => 'required|exists:departments,id',
            'owner_user_id' => 'required|exists:users,id',
            'unit_id' => 'nullable|exists:units,id',
            'parent_goal_id' => 'nullable|exists:goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Goal::create([
            'goal_cycle_id' => $request->input('goal_cycle_id'),
            'parent_goal_id' => $request->input('parent_goal_id'),
            'level' => 'staff',
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'department_id' => $request->input('department_id'),
            'unit_id' => $request->input('unit_id'),
            'owner_user_id' => $request->input('owner_user_id'),
            'status' => 'draft',
            'createdBy' => auth()->id(),
            'delete_status' => 0,
        ]);

        Alert::success('successful', 'Staff goal created');
        return redirect()->back();
    }

    public function alignGoal(Request $request, $goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'aligned_goal_id' => 'required|exists:goals,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        GoalAlignment::firstOrCreate([
            'goal_id' => $goal->id,
            'aligned_goal_id' => $request->input('aligned_goal_id'),
        ], [
            'createdBy' => auth()->id(),
            'delete_status' => 0,
        ]);

        $goal->update([
            'status' => 'aligned',
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Goal aligned');
        return redirect()->back();
    }

    public function submitToHrbp(Request $request, $goalId)
    {
        $goal = Goal::where('id', $goalId)->where('delete_status', 0)->firstOrFail();

        $goal->update([
            'status' => 'submitted',
            'submitted_to_hrbp_at' => now(),
            'updatedBy' => auth()->id(),
        ]);

        Alert::success('successful', 'Submitted to HRBP');
        return redirect()->back();
    }
}
