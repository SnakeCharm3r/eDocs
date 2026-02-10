<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use App\Models\Platform;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlatformUnitController extends Controller
{
    public function index(Request $request)
    {
        // Units page (department.units.index)
        // Accepts ?platform_id= to preselect a platform and load its units.
        $platformId = $request->integer('platform_id');

        // Left dropdown/list of platforms to pick from
        $platforms = Platform::orderBy('name')->get(['id', 'name']);

        $currentPlatform = null;
        $units = collect();

        if ($platformId) {
            // Load the selected platform with its units + incharge (and incharge->department for label)
            $currentPlatform = Platform::query()
                ->with([
                    'units' => fn($q) => $q->orderBy('name'),
                    'units.incharge:id,fname,mname,lname,username,email,deptId,department_id',
                    'units.incharge.department:id,dept_name',
                ])
                ->findOrFail($platformId, ['id', 'name', 'description']);

            $units = $currentPlatform->units;
        }

        // Departments list for “Assign Incharge” modal
        $departments = Departments::query()
            ->orderBy('dept_name')
            ->get(['id', 'dept_name']);

        return view('department.units.index', compact(
            'platforms',
            'currentPlatform',
            'units',
            'departments'
        ));
    }

    // Loads the modal content (form + list)
    public function modal(Platform $platform)
    {
        // Detect the users table department FK column you actually use (deptId / department_id / dept_id)
        $userDeptFk = Schema::hasColumn('users', 'deptId') ? 'deptId'
            : (Schema::hasColumn('users', 'department_id') ? 'department_id'
                : (Schema::hasColumn('users', 'dept_id') ? 'dept_id' : null));

        // Columns we need from users for showing a readable name in Blade
        $userCols = ['id', 'username', 'email'];
        foreach (['fname', 'mname', 'lname'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                $userCols[] = $col;
            }
        }
        if ($userDeptFk) {
            $userCols[] = $userDeptFk;
        }

        // Eager-load incharge with the columns above
        $units = $platform->units()
            ->with(['incharge' => function ($q) use ($userCols) {
                $q->select($userCols);
            }])
            ->orderBy('name')
            ->get();

        // Build Departments list (handles dept_name vs name vs deptName)
        $deptNameCol = Schema::hasColumn('departments', 'dept_name') ? 'dept_name'
            : (Schema::hasColumn('departments', 'name') ? 'name'
                : (Schema::hasColumn('departments', 'deptName') ? 'deptName' : null));

        $dq = Departments::query()->select('id');
        if ($deptNameCol) {
            $dq->addSelect($deptNameCol)->orderBy($deptNameCol);
        }
        $departments = $dq->get()->map(function ($d) use ($deptNameCol) {
            $d->display_name = $deptNameCol ? ($d->{$deptNameCol} ?? ('Dept #' . $d->id)) : ('Dept #' . $d->id);
            return $d;
        });

        // Pass everything the partial needs
        return view('department.platforms._units_modal', compact('platform', 'units', 'departments', 'userDeptFk'));
    }

    public function store(Request $request, Platform $platform)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:120|unique:units,name,NULL,id,platform_id,' . $platform->id,
            'description' => 'nullable|string|max:255',
            'locum_hours' => 'nullable|integer|min:1|max:24',
            'is_active'   => 'sometimes|boolean',
        ]);

        $unit = $platform->units()->create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'locum_hours' => $data['locum_hours'] ?? null,
            'is_active'   => $data['is_active'] ?? true,
        ]);

        return response()->json(['status' => 200, 'message' => 'Unit created.', 'data' => $unit]);
    }

    public function usersByDepartment(Request $request, Departments $department)
    {
        $search     = trim((string) $request->get('q', ''));
        $onlyActive = (bool) $request->boolean('active', true);

        // 1) Detect FK on users table
        $userDeptFk = null;
        foreach (['department_id', 'dept_id', 'departmentId', 'deptID'] as $cand) {
            if (Schema::hasColumn('users', $cand)) {
                $userDeptFk = $cand;
                break;
            }
        }

        // 2) Detect common pivot table names if no FK
        $pivotTable = null;
        foreach (['department_user', 'dept_user', 'department_users', 'dept_users'] as $pt) {
            if (Schema::hasTable($pt)) {
                $pivotTable = $pt;
                break;
            }
        }

        // 3) Detect “code” mapping (dept_code / code) if neither FK nor pivot exists
        $deptCodeCol = null;
        foreach (['dept_code', 'code', 'department_code'] as $cand) {
            if (Schema::hasColumn('departments', $cand)) {
                $deptCodeCol = $cand;
                break;
            }
        }
        $userDeptCodeCol = null;
        foreach (['dept_code', 'department_code', 'deptCode'] as $cand) {
            if (Schema::hasColumn('users', $cand)) {
                $userDeptCodeCol = $cand;
                break;
            }
        }

        // Build base query with safe selectable columns
        $q = User::query()->select('users.id');
        foreach (['name', 'first_name', 'last_name', 'username', 'email'] as $col) {
            if (Schema::hasColumn('users', $col)) $q->addSelect("users.$col");
        }
        if ($onlyActive && Schema::hasColumn('users', 'is_active')) {
            $q->where('users.is_active', 1);
        }

        // APPLY FILTER (choose the first working strategy)
        if ($userDeptFk) {
            $q->where("users.$userDeptFk", $department->getKey());
        } elseif ($pivotTable) {
            $q->join($pivotTable, "$pivotTable.user_id", '=', 'users.id')
                ->where("$pivotTable.department_id", $department->getKey());
        } elseif ($deptCodeCol && $userDeptCodeCol) {
            $deptCode = DB::table('departments')->where('id', $department->getKey())->value($deptCodeCol);
            if ($deptCode === null || $deptCode === '') {
                return response()->json(['data' => []]);
            }
            $q->where("users.$userDeptCodeCol", $deptCode);
        } else {
            return response()->json(['data' => []]);
        }

        // Optional text search
        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                foreach (['name', 'first_name', 'last_name', 'username', 'email'] as $col) {
                    if (Schema::hasColumn('users', $col)) {
                        $w->orWhere("users.$col", 'like', "%{$search}%");
                    }
                }
            });
        }

        // Map to display_name and sort
        $users = $q->get()->map(function ($u) {
            $c = [];
            if (isset($u->name) && trim($u->name) !== '') $c[] = trim($u->name);
            $fn = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            if ($fn !== '') $c[] = $fn;
            if (!empty($u->username)) $c[] = $u->username;
            if (!empty($u->email))    $c[] = $u->email;
            return ['id' => $u->id, 'display_name' => $c[0] ?? ('User #' . $u->id)];
        })->sortBy('display_name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return response()->json(['data' => $users]);
    }

    // Update: enforce department-user match on assign
    public function assignIncharge(Request $request, Platform $platform, Unit $unit)
    {
        if ($unit->platform_id !== $platform->id) abort(404);

        // Detect which FK column your units table actually uses
        $inchargeFk = Schema::hasColumn('units', 'incharge_user_id')
            ? 'incharge_user_id'
            : (Schema::hasColumn('units', 'incharge') ? 'incharge' : null);

        if (!$inchargeFk) {
            return response()->json(['status' => 500, 'message' => 'Units table has no in-charge FK column.'], 500);
        }

        // Detect user->department FK for validation
        $userDeptFk = Schema::hasColumn('users', 'department_id') ? 'department_id'
            : (Schema::hasColumn('users', 'dept_id') ? 'dept_id' : null);

        $validated = $request->validate([
            'user_id'       => 'nullable|exists:users,id',
            'department_id' => 'required_unless:user_id,null|exists:departments,id',
        ]);

        // Clear in-charge
        if (is_null($validated['user_id'])) {
            $unit->{$inchargeFk} = null;
            $unit->save();
            return response()->json(['status' => 200, 'message' => 'Incharge cleared.', 'data' => $unit->only(['id'])]);
        }

        // If we can, ensure user belongs to the chosen department
        if ($userDeptFk) {
            $belongs = User::where('id', $validated['user_id'])
                ->where($userDeptFk, $validated['department_id'])->exists();
            if (!$belongs) {
                return response()->json(['status' => 422, 'message' => 'Selected user is not in chosen department.'], 422);
            }
        }

        // Save using correct FK
        $unit->{$inchargeFk} = $validated['user_id'];
        $unit->save();

        return response()->json(['status' => 200, 'message' => 'Incharge assigned.', 'data' => $unit->only(['id'])]);
    }

    public function update(Request $request, Platform $platform, Unit $unit)
    {
        if ($unit->platform_id !== $platform->id) abort(404);

        $data = $request->validate([
            'name'        => 'required|string|max:120|unique:units,name,' . $unit->id . ',id,platform_id,' . $platform->id,
            'description' => 'nullable|string|max:255',
            'locum_hours' => 'nullable|integer|min:1|max:24',
            'is_active'   => 'sometimes|boolean',
        ]);

        $unit->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'locum_hours' => $data['locum_hours'] ?? $unit->locum_hours,
            'is_active'   => $data['is_active'] ?? $unit->is_active,
        ]);

        return response()->json(['status' => 200, 'message' => 'Unit updated.', 'data' => $unit]);
    }

    public function destroy(Platform $platform, Unit $unit)
    {
        if ($unit->platform_id !== $platform->id) abort(404);

        $unit->delete();

        return response()->json(['status' => 200, 'message' => 'Unit deleted.']);
    }
    public function unitsJson($platform)
    {
        $platformId = (int) $platform;

        // Detect the in-charge FK column on units
        $inchargeFk = null;
        foreach (['incharge_user_id', 'incharge'] as $cand) {
            if (Schema::hasColumn('units', $cand)) {
                $inchargeFk = $cand;
                break;
            }
        }

        // Do we link via pivot?
        $usesPivot = Schema::hasTable('platform_unit'); // adjust if your pivot name differs

        // Always include locum_hours so the UI can show per-unit requirements
        $q = \App\Models\Unit::query()->select(['units.id', 'units.name', 'units.locum_hours']);

        if ($usesPivot) {
            // belongsToMany via platform_unit(platform_id, unit_id)
            $q->join('platform_unit', function ($j) use ($platformId) {
                $j->on('platform_unit.unit_id', '=', 'units.id')
                    ->where('platform_unit.platform_id', '=', $platformId);
            });
        } else {
            // hasMany via units.platform_id
            if (Schema::hasColumn('units', 'platform_id')) {
                $q->where('units.platform_id', $platformId);
            } else {
                return response()->json(['units' => []]); // no way to link
            }
        }

        // Left-join users to ensure the in-charge user actually exists
        if ($inchargeFk) {
            $q->addSelect("units.$inchargeFk");
            $q->leftJoin('users', "users.id", '=', "units.$inchargeFk")
                ->addSelect(DB::raw("CASE WHEN units.$inchargeFk IS NOT NULL AND users.id IS NOT NULL THEN 1 ELSE 0 END AS has_incharge_flag"));
        } else {
            $q->addSelect(DB::raw("0 AS has_incharge_flag"));
        }

        $q->orderBy('units.name');
        $rows = $q->get();

        $units = $rows->map(function ($u) {
            return [
                'id'           => (int) $u->id,
                'name'         => (string) $u->name,
                'locum_hours'  => $u->locum_hours !== null ? (int) $u->locum_hours : null,
                'has_incharge' => ((int) ($u->has_incharge_flag ?? 0)) === 1,
            ];
        });

        return response()->json(['units' => $units]);
    }

}
