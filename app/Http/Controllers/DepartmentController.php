<?php

namespace App\Http\Controllers;

use App\Models\Departments;
use App\Models\Hec;
use App\Models\Division;
use App\Models\User;
use App\Models\JobTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index1()
    // {
    //     $departments = Departments::all();
    //     return view('department.index', compact('departments'));

    // }

    public function index1()
    {
        $hec = Hec::all();
        $departments = Departments::orderBy('dept_name', 'asc')->get();

        return view('department.index', compact('departments'));
    }

    public function index()
    {
        $hec = Hec::select('id', 'hec_level_name')->get(); // HEC levels for dropdown

        // Subquery: Get one head of department (line-manager) per department
        $hodSubquery = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'line-manager')
            ->select('users.deptId', 'users.fname', 'users.lname')
            ->groupBy('users.deptId', 'users.fname', 'users.lname');

        // Main query - exclude soft-deleted departments
        $departments = DB::table('departments')
            ->where(function($query) {
                $query->where('departments.delete_status', '!=', '1')
                      ->orWhereNull('departments.delete_status');
            })
            ->leftJoin('hecs', 'departments.hec_id', '=', 'hecs.id')
            ->leftJoinSub($hodSubquery, 'hods', function ($join) {
                $join->on('departments.id', '=', 'hods.deptId');
            })
            ->leftJoin('users as dept_users', 'departments.id', '=', 'dept_users.deptId') // for user count
            ->leftJoin('users as hec_members', 'departments.hec_member_id', '=', 'hec_members.id') // for HEC member
            ->select(
                'departments.id as dept_id',
                'departments.dept_name',
                'departments.description',
                'departments.clinical_or_non_clinical',
                'departments.hec_member_id',
                'hecs.hec_level_name',
                DB::raw('CONCAT(hods.fname, " ", hods.lname) as head_of_department'),
                DB::raw('CONCAT(hec_members.fname, " ", hec_members.lname) as hec_member_name'),
                DB::raw('COUNT(DISTINCT dept_users.id) as user_count')
            )
            ->groupBy(
                'departments.id',
                'departments.dept_name',
                'departments.description',
                'departments.clinical_or_non_clinical',
                'departments.hec_member_id',
                'hecs.hec_level_name',
                'hods.fname',
                'hods.lname',
                'hec_members.fname',
                'hec_members.lname'
            )
            ->orderBy('departments.dept_name', 'asc')
            ->get();

        // Load divisions and job titles count for each department
        $departments = collect($departments)->map(function ($dept) {
            $departmentModel = Departments::with('divisions')->withCount('jobTitles')->find($dept->dept_id);
            $dept->divisions = $departmentModel ? $departmentModel->divisions : collect();
            $dept->job_titles_count = $departmentModel ? $departmentModel->job_titles_count : 0;
            return $dept;
        });

        // Get all entities/divisions for filter dropdown
        $entities = Division::where(function($query) {
                $query->where('delete_status', '!=', '1')
                      ->orWhereNull('delete_status');
            })
            ->orderBy('name', 'asc')
            ->get();

        return view('department.index', compact('departments', 'hec', 'entities'));
    }

    public function create()
    {
        $hec = Hec::all();
        // dd($hec);
        return view('department.create', compact('hec'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'dept_name' => 'required',
            'description' => 'required',
            'clinical_or_non_clinical' => 'required|in:Clinical,Non-Clinical',
            'hec_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors(),
            ]);
        }

        $deptCheck = Departments::where('dept_name', $request->dept_name)->first();
        if ($deptCheck) {
            return response()->json([
                'status' => 400,
                'message' => 'Departments is already exist',
                'data' => $request->all()
            ]);
        }
        $dept = Departments::create([
            'dept_name' => $request->input('dept_name'),
            'description' => $request->input('description'),
            'clinical_or_non_clinical' => $request->input('clinical_or_non_clinical'),
            'hec_id' => $request->input('hec_id'),
        ]);
        
        // Load relationships for response
        $dept->load('hec');
        
        // Return JSON response for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 200,
                'message' => 'Department added successfully.',
                'data' => [
                    'dept_id' => $dept->id,
                    'dept_name' => $dept->dept_name,
                    'description' => $dept->description,
                    'clinical_or_non_clinical' => $dept->clinical_or_non_clinical,
                    'hec_level_name' => $dept->hec->hec_level_name ?? null,
                    'head_of_department' => null, // Will be populated if exists
                    'user_count' => 0
                ]
            ]);
        }
        
        Alert::success('successful', 'Department Added successfully');
        return redirect()->route('department.index')->with('success', 'Department added successfully.');
    }

    public function show(string $id)
    {
        $department = Departments::with(['hec', 'divisions', 'user'])
            ->findOrFail($id);
        
        return view('department.show', compact('department'));
    }

    public function edit(string $id)
    {
        $department = Departments::with(['hec', 'hecMember'])->findOrFail($id);

        $hecs = Hec::all();
        
        // Get HEC members (users with COO or CMS roles)
        $hecMembers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['coo', 'cms']);
        })->orderBy('fname')->orderBy('lname')->get();

        return view('department.edit', compact('department', 'hecs', 'hecMembers'));
    }


    public function update(Request $request, string $id)
    {
        //dd($request);
        $validator = Validator::make($request->all(), [
            'dept_name' => 'required',
            'description' => 'required',
            'hec_id' => 'nullable|exists:hecs,id',
            'hec_member_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors(),
            ]);
        }

        $department = Departments::findOrFail($id);
        $department->update([
            'dept_name' => $request->input('dept_name'),
            'description' => $request->input('description'),
            'hec_id' => $request->input('hec_id'),
            'hec_member_id' => $request->input('hec_member_id'),
        ]);
        // dd($department);
        Alert::success('Successfully', 'Department has been updated.');
        return redirect()->route('department.index')->with('success', 'Department updated successfully.');
    }



    public function destroy(string $id)
    {
        $dept = Departments::find($id);

        if (!$dept) {
            return response()->json([
                'status' => 404,
                'message' => 'Department not found',
            ]);
        }

        // Only check for staff (users) - HEC mapping and job titles do not prevent deletion
        // Check for any users (active or inactive)
        $totalUserCount = User::where('deptId', $dept->id)->count();
        if ($totalUserCount > 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Cannot delete department. There are ' . $totalUserCount . ' staff member(s) assigned to this department. Please reassign or remove all staff first.'
            ], 400);
        }

        // Note: HEC mapping (hec_id, hec_member_id) and job titles do not prevent deletion
        // Only staff members prevent deletion

        // Soft delete the department (set delete_status = 1)
        $dept->update([
            'delete_status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully!'
        ]);
    }

    /**
     * Display soft-deleted departments for permanent deletion
     */
    public function deleted()
    {
        $hec = Hec::select('id', 'hec_level_name')->get();

        // Subquery: Get one head of department (line-manager) per department
        $hodSubquery = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'line-manager')
            ->select('users.deptId', 'users.fname', 'users.lname')
            ->groupBy('users.deptId', 'users.fname', 'users.lname');

        // Query for soft-deleted departments only
        $deletedDepartments = DB::table('departments')
            ->where('departments.delete_status', '=', '1')
            ->leftJoin('hecs', 'departments.hec_id', '=', 'hecs.id')
            ->leftJoinSub($hodSubquery, 'hods', function ($join) {
                $join->on('departments.id', '=', 'hods.deptId');
            })
            ->leftJoin('users as dept_users', 'departments.id', '=', 'dept_users.deptId')
            ->leftJoin('users as hec_members', 'departments.hec_member_id', '=', 'hec_members.id')
            ->select(
                'departments.id as dept_id',
                'departments.dept_name',
                'departments.description',
                'departments.clinical_or_non_clinical',
                'departments.hec_member_id',
                'departments.delete_status',
                'hecs.hec_level_name',
                DB::raw('CONCAT(hods.fname, " ", hods.lname) as head_of_department'),
                DB::raw('CONCAT(hec_members.fname, " ", hec_members.lname) as hec_member_name'),
                DB::raw('COUNT(DISTINCT dept_users.id) as user_count')
            )
            ->groupBy(
                'departments.id',
                'departments.dept_name',
                'departments.description',
                'departments.clinical_or_non_clinical',
                'departments.hec_member_id',
                'departments.delete_status',
                'hecs.hec_level_name',
                'hods.fname',
                'hods.lname',
                'hec_members.fname',
                'hec_members.lname'
            )
            ->orderBy('departments.dept_name', 'asc')
            ->get();

        // Load divisions and job titles count for each department
        $deletedDepartments = collect($deletedDepartments)->map(function ($dept) {
            $departmentModel = Departments::with('divisions')->withCount('jobTitles')->find($dept->dept_id);
            $dept->divisions = $departmentModel ? $departmentModel->divisions : collect();
            $dept->job_titles_count = $departmentModel ? $departmentModel->job_titles_count : 0;
            return $dept;
        });

        return view('department.deleted', compact('deletedDepartments', 'hec'));
    }

    /**
     * Permanently delete a soft-deleted department
     */
    public function forceDelete(string $id)
    {
        $dept = Departments::where('id', $id)
            ->where('delete_status', '1')
            ->first();

        if (!$dept) {
            return response()->json([
                'status' => 404,
                'message' => 'Deleted department not found',
            ]);
        }

        // Permanently delete the department
        $dept->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department permanently deleted successfully!'
        ]);
    }

    public function locumSettings()
    {
        $departments = Departments::select(
            'id',
            'dept_name',
            'has_three_level_approval',
            'has_incharge_platform_flow',
            'has_incharge_lm_hec_flow'
        )
            ->orderBy('dept_name', 'asc')
            ->get();

        return view('department.locum-settings', compact('departments'));
    }



    public function updateLocumSettings(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'approval_flow' => 'required|in:standard,three_level,incharge_platform,incharge_lm_hec_hr', // <-- new
        ]);

        $department = Departments::findOrFail($data['department_id']);

        // Reset all toggles first (single source of truth)
        $department->has_three_level_approval   = false;
        $department->has_incharge_platform_flow = false;
        $department->has_incharge_lm_hec_flow   = false;

        switch ($data['approval_flow']) {
            case 'three_level':
                // legacy “3-level” flag (LM -> HEC -> HR after the in-charge step you already create in store())
                $department->has_three_level_approval = true;
                break;

            case 'incharge_platform':
                // In-Charge → Platform Manager(s) → (LM) → (HEC?) → HR
                $department->has_incharge_platform_flow = true;
                break;

            case 'incharge_lm_hec_hr':
                // NEW: In-Charge → LM → HEC → HR
                $department->has_incharge_lm_hec_flow = true;
                break;

            case 'standard':
            default:
                // In-Charge → LM → HR (no flags set)
                break;
        }

        $department->save();

        return back()->with('success', 'Approval flow updated successfully for ' . $department->dept_name);
    }

    public function oncallSettings()
    {
        $departments = Departments::select(
            'id',
            'dept_name',
            'has_oncall_three_level_approval'
        )
            ->orderBy('dept_name', 'asc')
            ->get();

        return view('department.oncall-settings', compact('departments'));
    }

    public function updateOncallSettings(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'approval_flow' => 'required|in:standard,three_level',
        ]);

        $department = Departments::findOrFail($data['department_id']);

        // Reset oncall approval flow
        $department->has_oncall_three_level_approval = false;

        switch ($data['approval_flow']) {
            case 'three_level':
                // Line Manager → HEC → HR
                $department->has_oncall_three_level_approval = true;
                break;

            case 'standard':
            default:
                // Line Manager → HR (no flag set)
                break;
        }

        $department->save();

        return back()->with('success', 'On-Call approval flow updated successfully for ' . $department->dept_name);
    }
}
