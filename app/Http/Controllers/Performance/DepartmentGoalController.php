<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Models\Departments;
use App\Models\Performance\DepartmentGoal;
use App\Models\Performance\HecGoalCascade;
use App\Models\Performance\PerformanceCycle;
use App\Models\Performance\StrategicGoal;
use App\Models\Performance\StrategicPerspective;
use App\Models\User;
use App\Services\GoalCascadeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentGoalController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $cycles = PerformanceCycle::orderByDesc('start_date')->get();
        $cycleId = $request->cycle_id ?? $cycles->first()?->id;

        $query = DepartmentGoal::where('performance_cycle_id', $cycleId)
            ->with([
                'department.head',
                'hodReviewer',
                'perspective',
                'strategicGoal.creator',
                'creator',
            ])
            ->withCount([
                'staffKpis as staff_kpis_total',
                'staffKpis as staff_kpis_finalized' => function ($q) {
                    $q->whereIn('workflow_status', [
                        'agreed',
                        'locked',
                        'submitted_to_hrbp',
                        'approved_by_hrbp',
                    ]);
                },
            ]);

        if (!$user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
            $hecMappedIds = GoalCascadeService::getDepartmentsForHecMember($user->id)->pluck('id');
            if ($hecMappedIds->isNotEmpty()) {
                $deptIds = $hecMappedIds;
                if ($user->deptId) {
                    $deptIds = $deptIds->push($user->deptId)->unique();
                }
                $query->whereIn('department_id', $deptIds);
            } else {
                $query->where('department_id', $user->deptId);
            }
        }

        $goals = $query->orderByRaw("FIELD(hod_review_status,'pending','agreed','rejected','waived')")->orderByDesc('created_at')->get();

        $isHod      = $user->hasAnyRole(['line-manager', 'acting-line-manager']);
        $isHec      = count(array_intersect(
            $user->roles->pluck('name')->map(fn($r) => strtolower($r))->toArray(),
            ['coo','cfo','cms','ccdro']
        )) > 0;
        $canManage  = $user->hasAnyRole(['Super-Admin', 'admin', 'hr', 'ceo']);

        $pendingReview   = $goals->where('hod_review_status', 'pending')->count();
        $agreedCount     = $goals->where('hod_review_status', 'agreed')->count();
        $rejectedCount   = $goals->where('hod_review_status', 'rejected')->count();
        $totalStaffKpis  = $goals->sum('staff_kpis_total');
        $finalStaffKpis  = $goals->sum('staff_kpis_finalized');

        return view('performance.department-goals.index', compact(
            'cycles', 'goals', 'cycleId',
            'isHod', 'isHec', 'canManage',
            'pendingReview', 'agreedCount', 'rejectedCount',
            'totalStaffKpis', 'finalStaffKpis'
        ));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $cycles = PerformanceCycle::orderByDesc('start_date')->get();
        $cycleId = $request->cycle_id ?? $cycles->first()?->id;

        $strategicGoals = StrategicGoal::where('performance_cycle_id', $cycleId)
            ->with(['perspective', 'owner.roles'])
            ->orderBy('title')
            ->get();
        $perspectives = StrategicPerspective::where('performance_cycle_id', $cycleId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $hecMapped = GoalCascadeService::getDepartmentsForHecMember($user->id)
            ->filter(fn ($d) => (int) $d->delete_status === 0)
            ->sortBy('dept_name')
            ->values();

        $hecMultiDept = false;
        if ($user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
            $departments = Departments::where('delete_status', 0)->orderBy('dept_name')->get();
        } elseif ($hecMapped->isNotEmpty()) {
            $departments = $hecMapped;
            $hecMultiDept = true;
        } else {
            $departments = Departments::where('id', $user->deptId)->get();
        }

        $prefillStrategicGoalId = $request->query('strategic_goal_id');
        if ($prefillStrategicGoalId && !$strategicGoals->contains('id', (int) $prefillStrategicGoalId)) {
            $prefillStrategicGoalId = null;
        }

        return view('performance.department-goals.create', compact(
            'cycles', 'strategicGoals', 'perspectives', 'departments', 'cycleId', 'hecMultiDept', 'prefillStrategicGoalId'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $hecMappedIds = GoalCascadeService::getDepartmentsForHecMember($user->id)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $useHecMulti = !empty($hecMappedIds) && !$user->hasAnyRole(['Super-Admin', 'admin', 'hr']);

        $kpiRules = [
            'definition' => 'nullable|string',
            'measurement' => 'nullable|string|max:255',
            'data_source' => 'nullable|string|max:255',
            'standard' => 'nullable|string|max:255',
            'target' => 'nullable|string|max:255',
            'baseline' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:100',
            'timeline' => 'nullable|string|max:255',
            'means_of_verification' => 'nullable|string|max:255',
        ];

        $baseRules = [
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'strategic_goal_id' => 'nullable|exists:strategic_goals,id',
            'hec_cascade_id' => 'nullable|exists:hec_goal_cascades,id',
            'strategic_perspective_id' => 'required|exists:strategic_perspectives,id',
            'status' => 'nullable|string|in:draft,active,completed,cancelled',
        ];

        if ($useHecMulti) {
            $validated = $request->validate(array_merge($baseRules, $kpiRules, [
                'department_ids' => ['required', 'array', 'min:1'],
                'department_ids.*' => ['integer', Rule::in($hecMappedIds)],
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]));
            $deptIdsToCreate = array_values(array_unique(array_map('intval', $validated['department_ids'])));
            $hasStrategic = !empty($validated['strategic_goal_id']);
            $hasTitle = isset($validated['title']) && trim($validated['title']) !== '';
            if (!$hasStrategic && !$hasTitle) {
                return redirect()->back()
                    ->withErrors(['title' => 'Link a strategic goal or enter a goal title.'])
                    ->withInput();
            }
        } else {
            $validated = $request->validate(array_merge($baseRules, $kpiRules, [
                'department_id' => 'required|exists:departments,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]));
            if (!$user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
                $allowed = array_values(array_unique(array_filter(array_merge(
                    [$user->deptId ? (int) $user->deptId : null],
                    $hecMappedIds
                ))));
                if (!in_array((int) $validated['department_id'], $allowed, true)) {
                    abort(403, 'You cannot create goals for this department.');
                }
            }
            $deptIdsToCreate = [(int) $validated['department_id']];
        }

        $strategicGoal = null;
        if (!empty($validated['strategic_goal_id'])) {
            $strategicGoal = StrategicGoal::find($validated['strategic_goal_id']);
            if (!$strategicGoal || (int) $strategicGoal->performance_cycle_id !== (int) $validated['performance_cycle_id']) {
                return redirect()->back()
                    ->withErrors(['strategic_goal_id' => 'Strategic goal must belong to the selected performance cycle.'])
                    ->withInput();
            }
        }

        $title = $strategicGoal ? $strategicGoal->title : ($validated['title'] ?? '');
        $description = $validated['description'] ?? null;
        if ($strategicGoal && ($description === null || $description === '')) {
            $description = $strategicGoal->description;
        }

        $hecCascadeId = $validated['hec_cascade_id'] ?? null;
        if (!$hecCascadeId && $strategicGoal && $user) {
            $cascade = HecGoalCascade::where('strategic_goal_id', $strategicGoal->id)
                ->where('hec_user_id', $user->id)
                ->orderByRaw('department_id IS NULL DESC')
                ->first();
            $hecCascadeId = $cascade?->id;
        }

        $payload = [
            'performance_cycle_id' => $validated['performance_cycle_id'],
            'strategic_goal_id' => $validated['strategic_goal_id'] ?? null,
            'hec_cascade_id' => $hecCascadeId,
            'strategic_perspective_id' => $validated['strategic_perspective_id'],
            'title' => $title,
            'description' => $description,
            'definition' => $validated['definition'] ?? null,
            'measurement' => $validated['measurement'] ?? null,
            'data_source' => $validated['data_source'] ?? null,
            'standard' => $validated['standard'] ?? null,
            'target' => $validated['target'] ?? null,
            'baseline' => $validated['baseline'] ?? null,
            'weight' => $validated['weight'] ?? null,
            'timeline' => $validated['timeline'] ?? null,
            'means_of_verification' => $validated['means_of_verification'] ?? null,
            'status' => $validated['status'] ?? 'draft',
            'created_by' => auth()->id(),
        ];

        $count = 0;
        DB::transaction(function () use ($payload, $deptIdsToCreate, $user, &$count) {
            foreach ($deptIdsToCreate as $deptId) {
                $hodMeta = $this->initialHodReviewForDepartment($user, $deptId);
                $row = array_merge($payload, ['department_id' => $deptId], $hodMeta);
                if (($hodMeta['hod_review_status'] ?? '') === DepartmentGoal::HOD_REVIEW_PENDING) {
                    $row['status'] = 'draft';
                }
                DepartmentGoal::create($row);
                $count++;
            }
        });

        $msg = $count === 1
            ? 'Department KPI created successfully.'
            : "Created {$count} department KPIs (one per selected department).";

        return redirect()->route('performance.department-goals.index', ['cycle_id' => $validated['performance_cycle_id']])
            ->with('success', $msg);
    }

    public function show(DepartmentGoal $departmentGoal)
    {
        $departmentGoal->load([
            'department.head',
            'strategicGoal.creator',
            'perspective',
            'hecCascade',
            'hodReviewer',
            'creator',
            'staffKpis.user',
        ]);

        $departmentGoal->loadCount([
            'staffKpis as staff_kpis_total',
            'staffKpis as staff_kpis_finalized' => function ($q) {
                $q->whereIn('workflow_status', [
                    'agreed',
                    'locked',
                    'submitted_to_hrbp',
                    'approved_by_hrbp',
                ]);
            },
        ]);

        $dept = $departmentGoal->department;
        if ($dept && $dept->has_platforms) {
            $departmentGoal->load('platformGoals');
        }
        if ($dept && $dept->has_units) {
            $departmentGoal->load('unitGoals.unit');
        }

        return view('performance.department-goals.show', compact('departmentGoal'));
    }

    public function edit(DepartmentGoal $departmentGoal)
    {
        $user = auth()->user();
        $cycles = PerformanceCycle::orderByDesc('start_date')->get();
        $strategicGoals = StrategicGoal::where('performance_cycle_id', $departmentGoal->performance_cycle_id)->get();
        $perspectives = StrategicPerspective::where('performance_cycle_id', $departmentGoal->performance_cycle_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $hecMapped = GoalCascadeService::getDepartmentsForHecMember($user->id)
            ->filter(fn ($d) => (int) $d->delete_status === 0)
            ->sortBy('dept_name')
            ->values();

        if ($user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
            $departments = Departments::where('delete_status', 0)->orderBy('dept_name')->get();
        } elseif ($hecMapped->isNotEmpty()) {
            $departments = $hecMapped;
        } else {
            $departments = Departments::where('id', $user->deptId)->get();
        }

        $goal = $departmentGoal;

        return view('performance.department-goals.edit', compact(
            'departmentGoal', 'goal', 'cycles', 'strategicGoals', 'perspectives', 'departments'
        ));
    }

    public function update(Request $request, DepartmentGoal $departmentGoal)
    {
        $validated = $request->validate([
            'performance_cycle_id' => 'required|exists:performance_cycles,id',
            'strategic_goal_id' => 'nullable|exists:strategic_goals,id',
            'hec_cascade_id' => 'nullable|exists:hec_goal_cascades,id',
            'strategic_perspective_id' => 'required|exists:strategic_perspectives,id',
            'department_id' => 'required|exists:departments,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'definition' => 'nullable|string',
            'measurement' => 'nullable|string|max:255',
            'data_source' => 'nullable|string|max:255',
            'standard' => 'nullable|string|max:255',
            'target' => 'nullable|string|max:255',
            'baseline' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0|max:100',
            'timeline' => 'nullable|string|max:255',
            'means_of_verification' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,active,completed,cancelled',
        ]);

        $user = auth()->user();
        if (!$user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
            $hecMappedIds = GoalCascadeService::getDepartmentsForHecMember($user->id)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $allowed = array_values(array_unique(array_filter(array_merge(
                [$user->deptId ? (int) $user->deptId : null],
                $hecMappedIds
            ))));
            if (!in_array((int) $validated['department_id'], $allowed, true)) {
                abort(403, 'You cannot assign this goal to that department.');
            }
        }

        $departmentGoal->update($validated);

        return redirect()->route('performance.department-goals.index', ['cycle_id' => $departmentGoal->performance_cycle_id])
            ->with('success', 'Department KPI updated successfully.');
    }

    public function destroy(DepartmentGoal $departmentGoal)
    {
        $cycleId = $departmentGoal->performance_cycle_id;
        $departmentGoal->delete();

        return redirect()->route('performance.department-goals.index', ['cycle_id' => $cycleId])
            ->with('success', 'Department KPI deleted successfully.');
    }

    public function hodAgree(Request $request, DepartmentGoal $departmentGoal)
    {
        $this->authorizeHodResponse($departmentGoal);

        if ($departmentGoal->hod_review_status !== DepartmentGoal::HOD_REVIEW_PENDING) {
            return redirect()->back()->with('error', 'This department KPI is not awaiting HOD agreement.');
        }

        $request->validate([
            'comment' => 'nullable|string|max:2000',
        ]);

        $departmentGoal->update([
            'hod_review_status' => DepartmentGoal::HOD_REVIEW_AGREED,
            'hod_reviewed_at' => now(),
            'hod_reviewed_by' => auth()->id(),
            'hod_review_comment' => $request->comment,
            'status' => $departmentGoal->status === 'draft' ? 'active' : $departmentGoal->status,
        ]);

        return redirect()->back()->with('success', 'You have agreed to this department KPI. It can now proceed in the cascade.');
    }

    public function hodReject(Request $request, DepartmentGoal $departmentGoal)
    {
        $this->authorizeHodResponse($departmentGoal);

        if ($departmentGoal->hod_review_status !== DepartmentGoal::HOD_REVIEW_PENDING) {
            return redirect()->back()->with('error', 'This department KPI is not awaiting HOD agreement.');
        }

        $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $departmentGoal->update([
            'hod_review_status' => DepartmentGoal::HOD_REVIEW_REJECTED,
            'hod_reviewed_at' => now(),
            'hod_reviewed_by' => auth()->id(),
            'hod_review_comment' => $request->comment,
            'status' => 'draft',
        ]);

        return redirect()->back()->with('success', 'You have sent this KPI back. The HEC member can revise and resubmit.');
    }

    protected function authorizeHodResponse(DepartmentGoal $departmentGoal): void
    {
        $user = auth()->user();
        if ($user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
            return;
        }
        if (!$this->userIsHeadOfDepartment($user, (int) $departmentGoal->department_id)) {
            abort(403, 'Only the department Head of Department (Line Manager) or HR/Admin can respond here.');
        }
    }

    protected function userIsHeadOfDepartment(User $user, int $departmentId): bool
    {
        if (!$user->deptId || (int) $user->deptId !== (int) $departmentId) {
            return false;
        }

        return $user->hasAnyRole(['line-manager', 'acting-line-manager']);
    }

    /**
     * When an HEC member creates a KPI for a mapped department, the department HOD must agree.
     * Line managers creating for their own department are treated as agreed immediately.
     */
    protected function initialHodReviewForDepartment(User $user, int $departmentId): array
    {
        if ($user->hasAnyRole(['Super-Admin', 'admin', 'hr'])) {
            return [
                'hod_review_status' => DepartmentGoal::HOD_REVIEW_WAIVED,
                'hod_reviewed_at' => null,
                'hod_reviewed_by' => null,
                'hod_review_comment' => null,
            ];
        }

        if ($this->userIsHeadOfDepartment($user, $departmentId)) {
            return [
                'hod_review_status' => DepartmentGoal::HOD_REVIEW_AGREED,
                'hod_reviewed_at' => now(),
                'hod_reviewed_by' => $user->id,
                'hod_review_comment' => null,
            ];
        }

        $dept = Departments::find($departmentId);
        if ($dept && (int) $dept->hec_member_id === (int) $user->id) {
            return [
                'hod_review_status' => DepartmentGoal::HOD_REVIEW_PENDING,
                'hod_reviewed_at' => null,
                'hod_reviewed_by' => null,
                'hod_review_comment' => null,
            ];
        }

        return [
            'hod_review_status' => DepartmentGoal::HOD_REVIEW_PENDING,
            'hod_reviewed_at' => null,
            'hod_reviewed_by' => null,
            'hod_review_comment' => null,
        ];
    }
}
