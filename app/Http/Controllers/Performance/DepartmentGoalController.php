<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceCycle;
use App\Models\Performance\StrategicGoal;
use App\Models\Performance\DepartmentGoal;
use App\Models\Departments;
use Illuminate\Http\Request;

class DepartmentGoalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cycles = PerformanceCycle::orderByDesc('start_date')->get();
        $cycleId = $request->cycle_id ?? $cycles->first()?->id;

        $query = DepartmentGoal::where('performance_cycle_id', $cycleId)
            ->with('creator', 'department', 'strategicGoal');

        // Line managers only see their own department goals
        if ($user->hasRole('line-manager') && !$user->hasRole(['Super-Admin', 'admin', 'hr', 'coo', 'cfo', 'cms', 'ccdro'])) {
            $query->where('department_id', $user->deptId);
        }

        $goals = $query->orderByDesc('created_at')->get();
        $strategicGoals = StrategicGoal::where('performance_cycle_id', $cycleId)->where('status', 'active')->get();
        $departments = Departments::orderBy('dept_name')->get();

        return view('performance.department-goals.index', compact('goals', 'cycles', 'cycleId', 'strategicGoals', 'departments'));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $cycles = PerformanceCycle::where('status', '!=', 'closed')->orderByDesc('start_date')->get();
        $cycleId = $request->cycle_id ?? $cycles->first()?->id;
        $strategicGoals = StrategicGoal::where('performance_cycle_id', $cycleId)->where('status', 'active')->get();

        if ($user->hasRole('line-manager') && !$user->hasRole(['Super-Admin', 'admin', 'hr'])) {
            $departments = Departments::where('id', $user->deptId)->get();
        } else {
            $departments = Departments::orderBy('dept_name')->get();
        }

        return view('performance.department-goals.create', compact('cycles', 'cycleId', 'strategicGoals', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'strategic_goal_id' => 'nullable|exists:strategic_goals,id',
            'department_id' => 'required|exists:departments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|numeric|min:0|max:100',
        ]);

        DepartmentGoal::create([
            ...$request->only(['performance_cycle_id', 'strategic_goal_id', 'department_id', 'title', 'description', 'weight']),
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('performance.department-goals.index', ['cycle_id' => $request->performance_cycle_id])
            ->with('success', 'Department goal created successfully.');
    }

    public function show(DepartmentGoal $departmentGoal)
    {
        $departmentGoal->load('creator', 'cycle', 'department', 'strategicGoal', 'platformGoals.platform');
        return view('performance.department-goals.show', compact('departmentGoal'));
    }

    public function edit(DepartmentGoal $departmentGoal)
    {
        $cycles = PerformanceCycle::where('status', '!=', 'closed')->orderByDesc('start_date')->get();
        $strategicGoals = StrategicGoal::where('performance_cycle_id', $departmentGoal->performance_cycle_id)
            ->where('status', 'active')->get();
        $departments = Departments::orderBy('dept_name')->get();

        return view('performance.department-goals.edit', compact('departmentGoal', 'cycles', 'strategicGoals', 'departments'));
    }

    public function update(Request $request, DepartmentGoal $departmentGoal)
    {
        $request->validate([
            'strategic_goal_id' => 'nullable|exists:strategic_goals,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:draft,active,completed,cancelled',
        ]);

        $departmentGoal->update($request->only(['strategic_goal_id', 'title', 'description', 'weight', 'status']));

        return redirect()->route('performance.department-goals.index', ['cycle_id' => $departmentGoal->performance_cycle_id])
            ->with('success', 'Department goal updated.');
    }

    public function destroy(DepartmentGoal $departmentGoal)
    {
        $cycleId = $departmentGoal->performance_cycle_id;
        $departmentGoal->delete();
        return redirect()->route('performance.department-goals.index', ['cycle_id' => $cycleId])
            ->with('success', 'Department goal deleted.');
    }
}
