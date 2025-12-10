<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Policy;
use App\Models\Departments;
use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\DepartmentPolicy;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;

use Illuminate\Support\Facades\Request as FacadesRequest;

class PolicyController extends Controller
{
    // public function index()
    // {
    //     $policies = Policy::all();
    //     $departmentPolicies = DepartmentPolicy::with('department')->get();
    //     return view('policies.index', compact('policies','departmentPolicies'));
    // }
public function index(Request $request)
{
    $departmentId = $request->query('department_id');

    // Fetch organization-wide policies
    $policies = Policy::all();

    // Fetch department-specific policies with departments
    $departmentPoliciesQuery = DepartmentPolicy::with('department');

    if ($departmentId) {
        $departmentPoliciesQuery->where('department_id', $departmentId);
    }

    $departmentPolicies = $departmentPoliciesQuery->get();

    // Group by title (or use 'id' if each policy is unique by id)
    $groupedPolicies = $departmentPolicies->groupBy('title')->map(function ($items) {
        $first = $items->first();
        return (object) [
            'id' => $first->id,
            'title' => $first->title,
            'content' => $first->content,
            'departments' => $items->pluck('department.dept_name')->unique()->filter()->values(),
        ];
    });

    $departments = Departments::all(); // For the department filter dropdown
    $departments = Departments::orderBy('dept_name', 'asc')->get();
    return view('policies.index', [
        'policies' => $policies,
        'groupedPolicies' => $groupedPolicies,
        'departments' => $departments,
    ]);
}


    public function index1()
    {
        $policies = Policy::all();
        return view('policies.index1', compact('policies'));
    }
    public function createDepartmentPolicy()
    {
        $departments = Departments::all();
        return view('policies.create_department', compact('departments'));
    }

public function storeDepartmentPolicy(Request $request)
    {
        $request->validate([
            'department_id' => 'required|array',
            'department_id.*' => 'exists:departments,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:90000',
        ]);

        // Create a policy record for each selected department
        foreach ($request->department_id as $deptId) {
            DepartmentPolicy::create([
                'department_id' => $deptId,
                'title' => $request->title,
                'content' => $request->content,
                'created_by' => auth()->id(), // Add the authenticated user's ID
            ]);
        }
     Alert::success('success', 'policy created successfully');

        return redirect()->route('policies.index')->with('success', 'Department policy created successfully for selected departments.');
    }

public function editDepartmentPolicy($id)
{
    // Retrieve the policy by ID along with its related department
    $departmentPolicy = DepartmentPolicy::with('department')->findOrFail($id);

    // Retrieve all departments for the department selection dropdown
    $departments = Departments::all();

    // Return the view with the policy and departments
         Alert::success('success', 'policy updated successfully');

    return view('policies.edit-department-policies', compact('departmentPolicy', 'departments'));
}


public function updateDepartmentPolicy(Request $request, $id)
{
    $request->validate([
        'department_id' => 'required|array',
        'department_id.*' => 'exists:departments,id',
        'title' => 'required|string|max:255',
        'content' => 'required|string|max:90000',
    ]);

    // Find the department policy
    $departmentPolicy = DepartmentPolicy::findOrFail($id);

    // Update the policy fields
    $departmentPolicy->update([
        'title' => $request->title,
        'content' => $request->content,
    ]);

    // Sync the selected departments (many-to-many relation)
    $departmentPolicy->departments()->sync($request->department_id);
     Alert::success('success', 'policy updated successfully');

    return redirect()->route('policies.index')->with('success', 'Department policy updated successfully.');
}



    public function destroydept(DepartmentPolicy $departmentPolicy)
    {
        $departmentPolicy->departments()->detach(); // Remove pivot table entries
        $departmentPolicy->delete();
             Alert::success('success', 'policy deleted successfully');

        return redirect()->route('policies.index')->with('success', 'Department policy deleted successfully.');
    }

    public function user()
    {
        $policies = Policy::all();
        $user = auth()->user();
        $departmentPolicies = DepartmentPolicy::where('department_id', $user->deptId)->get();
        return view('policies.user', ['policies' => $policies,'user' => $user,'departmentPolicies' => $departmentPolicies]);
    }
    public function create()
    {
        return view('policies.create');
    }

    public function store(Request $request)
{
    // dd($request->all()); // This will dump the request data and halt the process
    $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'required|string|max:90000',
    ]);

    Policy::create($request->all());

    return redirect()->route('policies.index')->with('success', 'Policy created successfully.');
}


    public function show(Policy $policy)
    {
        return view('policies.show', compact('policy'));
    }

    public function edit(Policy $policy)
    {
        return view('policies.edit', compact('policy'));
    }

    public function update(Request $request, Policy $policy)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $policy->update($request->all());

        return redirect()->route('policies.index')->with('success', 'Policy updated successfully.');
    }

    public function destroy(Policy $policy)
    {
        $policy->delete();
        return redirect()->route('policies.index')->with('success', 'Policy deleted successfully.');
    }

    public function accept(Request $request)
    {
        $request->user()->update(['accepted_policies' => true]);
        return redirect()->route('home');
    }


    public function downloadPolicy(Request $request, $id)
    {
        // Find the user
        $user = User::findOrFail($id);

        $request->validate([
            'policy_id' => 'required|integer|exists:policies,id',
        ]);

        // Find the policy
        $policy = Policy::findOrFail($request->input('policy_id'));
        if (!$policy) {
            return response()->json(['error' => 'No policy available to download'], 404);
        }

        // Generate PDF
        $pdf = PDF::loadView('pdf.index', [
            'policy' => $policy,
            'user' => $user,
        ]);

        // Download PDF
        return $pdf->download($policy->title . '.pdf');
    }

    // Download multiple policies
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

        // Process signature for PDF
        $signaturePath = null;
        if ($user->signature) {
            try {
                $signatureData = $user->signature;
                
                // Extract base64 data if it's a data URI
                if (strpos($signatureData, 'data:image') !== false) {
                    $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
                }
                
                // Decode and save temporarily
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

        // Generate PDF with multiple policies
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
        
        // Clean up temp file after download
        $response = $pdf->download($filename);
        
        if ($signaturePath && file_exists($signaturePath)) {
            register_shutdown_function(function() use ($signaturePath) {
                @unlink($signaturePath);
            });
        }
        
        return $response;
    }

    // Preview multiple policies
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



}
