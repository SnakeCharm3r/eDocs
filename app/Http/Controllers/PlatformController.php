<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use App\Models\Platform;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class PlatformController extends Controller
{
    public function index(Request $request)
    {
        // Load just what the Platforms index needs
        $platforms = Platform::query()
            ->withCount('units')
            ->with('manager:id,fname,mname,lname,username,email')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'manager_user_id', 'locum_hours']);

        // Departments list for the “Assign Manager” modal (department -> users)
        // Keep payload light and build a display_name the Blade can use.
        $departments = Departments::query()
            ->select(['id'])
            ->when(Schema::hasColumn('departments', 'dept_name'), fn($q) => $q->addSelect('dept_name'))
            ->when(!Schema::hasColumn('departments', 'dept_name') && Schema::hasColumn('departments', 'name'), fn($q) => $q->addSelect('name'))
            ->orderByRaw(
                Schema::hasColumn('departments', 'dept_name') ? 'dept_name' : (Schema::hasColumn('departments', 'name') ? 'name' : 'id')
            )
            ->get()
            ->map(function ($d) {
                $label = $d->dept_name ?? ($d->name ?? null);
                $d->display_name = $label ?: ('Dept #' . $d->id);
                return $d;
            });

        // Uses department.platforms.index
        return view('department.platforms.index', compact('platforms', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:platforms,name',
            'description' => 'nullable|string|max:255',
            'locum_hours'  => ['required', 'integer', 'in:8,12'],
        ]);
        Platform::create($data);
        return back()->with('success', 'Platform created.');
    }

    public function edit(Platform $platform)
    {
        return response()->json($platform);
    }

    public function update(Request $request, Platform $platform)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:platforms,name,' . $platform->id,
            'description' => 'nullable|string|max:255',
            'locum_hours'  => ['required', 'integer', 'in:8,12'],
        ]);
        $platform->update($data);
        return back()->with('success', 'Platform updated.');
    }

    public function destroy(Platform $platform)
    {
        $platform->delete();
        return back()->with('success', 'Platform deleted.');
    }

    // ============= Assign/Clear Platform Manager ============
    public function assignManager(Request $request, Platform $platform)
    {
        $data = $request->validate([
            'user_id'       => ['nullable', 'integer', Rule::exists('users', 'id')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        // Clearing manager is allowed
        $userId = $data['user_id'] ?? null;
        if ($userId === null) {
            $platform->update(['manager_user_id' => null]);
            return response()->json(['message' => 'Manager cleared.']);
        }

        // Optional: if department_id is provided, ensure the user belongs to that department
        if (!empty($data['department_id'])) {
            $deptFk = $this->detectUserDepartmentFk();
            if ($deptFk) {
                $belongs = User::where('id', $userId)
                    ->where($deptFk, $data['department_id'])
                    ->exists();
                if (!$belongs) {
                    return response()->json([
                        'message' => 'Selected user is not in chosen department.'
                    ], 422);
                }
            }
        }

        // 1) Reject if user is in-charge of ANY unit
        $isIncharge = Unit::where('incharge_user_id', $userId)->exists();
        if ($isIncharge) {
            return response()->json([
                'message' => 'This user is currently an in-charge of a unit and cannot be assigned as a platform manager.',
            ], 422);
        }

        // 2) Reject if user already manages a different platform
        $managesOther = Platform::where('manager_user_id', $userId)
            ->where('id', '!=', $platform->id)
            ->exists();
        if ($managesOther) {
            return response()->json([
                'message' => 'This user already manages another platform.',
            ], 422);
        }

        // Assign
        $platform->update(['manager_user_id' => $userId]);

        return response()->json(['message' => 'Manager updated.']);
    }

    // ============= AJAX user search =============
    // Still useful elsewhere; now optionally filters by department_id if provided.
    public function searchUsers(Request $request)
    {
        $q             = trim((string) $request->get('q', ''));
        $platformId    = $request->integer('platform_id');
        $departmentId  = $request->integer('department_id'); // optional filter
        $currentMgr    = $platformId ? Platform::find($platformId)?->manager_user_id : null;

        // Users who are in-charge of any unit
        $inchargeIds = Unit::whereNotNull('incharge_user_id')
            ->pluck('incharge_user_id')->unique()->values();

        // Users who are managers of any platform (except this platform's current manager)
        $managerIds = Platform::whereNotNull('manager_user_id')
            ->when($currentMgr, fn($qq) => $qq->where('manager_user_id', '!=', $currentMgr))
            ->pluck('manager_user_id')->unique()->values();

        $users = User::query()
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($w) use ($q) {
                    $w->where('fname', 'like', "%$q%")
                        ->orWhere('mname', 'like', "%$q%")
                        ->orWhere('lname', 'like', "%$q%")
                        ->orWhere('username', 'like', "%$q%")
                        ->orWhere('email', 'like', "%$q%");
                });
            })
            ->when($departmentId && ($deptFk = $this->detectUserDepartmentFk()), function ($qr) use ($departmentId, $deptFk) {
                $qr->where($deptFk, $departmentId);
            })
            ->whereNotIn('id', $inchargeIds) // not a unit in-charge
            ->whereNotIn('id', $managerIds)  // not a manager elsewhere
            ->when($currentMgr, fn($qr) => $qr->orWhere('id', $currentMgr)) // allow current mgr to stay visible
            ->limit(20)
            ->get(['id', 'fname', 'mname', 'lname', 'username', 'email']);

        $results = $users->map(function ($u) {
            $full = trim(trim(($u->fname ?? '') . ' ' . ($u->mname ?? '')) . ' ' . ($u->lname ?? ''));
            $text = $full !== '' ? $full : ($u->username ?? ($u->email ?? ('User #' . $u->id)));
            return ['id' => $u->id, 'text' => $text];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * JSON meta for a platform (does it have a manager?)
     * Route: GET /platforms/{platform}/meta/json  (name: platforms.meta.json)
     */
    public function platformMeta($platform)
    {
        $p = Platform::select('id', 'manager_user_id')->findOrFail((int)$platform);

        return response()->json([
            'has_manager'     => (bool) $p->manager_user_id,
            'manager_user_id' => $p->manager_user_id ? (int) $p->manager_user_id : null,
            'locum_hours'      => (int) $p->locum_hours,
        ]);
    }

    /**
     * Detect department FK column on users table.
     * Returns one of: 'department_id' | 'dept_id' | 'deptId' | null
     */
    private function detectUserDepartmentFk(): ?string
    {
        foreach (['department_id', 'dept_id', 'deptId'] as $cand) {
            if (Schema::hasColumn('users', $cand)) {
                return $cand;
            }
        }
        return null;
    }
}
