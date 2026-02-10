<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\JobTitle;
use App\Models\Department;
use App\Models\Departments;
use Illuminate\Http\Request;
use App\Models\JobDescription;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use Barryvdh\DomPDF\Facade\PDF;

use Auth;
use RealRashid\SweetAlert\Facades\Alert;

class JobDescriptionController extends Controller
{


public function index()
{
    $query = JobDescription::with([
        'user',
        'jobTitle',
        'department',
        'workflow.histories' // Ensure this matches your actual relationship name
    ]);

    // Filter for non-HR users
    if (!Auth::user()->hasRole('hr')) {
        $query->whereHas('workflow.histories', function($q) {
            $q->where('attended_by', Auth::id());
        });
    }

    $jobDescriptions = $query->get();
    $jobTitles = JobTitle::all();
    $departments = Departments::all();

    $jobDescriptions = JobDescription::with(['user', 'workflow.histories'])
        ->orderBy('created_at', 'desc')
        ->get();
    return view('job_descriptions.index', compact(
        'jobDescriptions',
        'jobTitles',
        'departments'
    ));
}


    public function create()
    {
        $users = User::with(['jobTitle', 'department'])->get();
        $users = User::all();
        $jobTitles = JobTitle::all();
        $departments = Departments::all();
        // dd($users);
        return view('job_descriptions.create', compact('users', 'jobTitles', 'departments'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'employee_type' => 'required|in:existing,new',
            'user_id' => ['nullable', 'required_if:employee_type,existing', 'exists:users,id'],
            'department_id' => 'required|string',
            'technical_job_level' => 'nullable|string',
            'job_title' => 'required|string',
            'jobs_responsible_for' => 'nullable|string',
            'region_location' => 'nullable|string',
            'working_hours' => 'nullable|string',
            'job_review_date' => 'nullable|date',
            'job_grade' => 'nullable|string',
            'grade_job_holder' => 'nullable|string',
            'employees_managed' => 'nullable|string',
            'reports_to' => 'nullable|string',
            'region_location' => 'nullable|string',
            'technical_job_level' => 'nullable|string',
            'grade_difference_reason' => 'nullable|string',
            'purpose' => 'nullable|string',
            'accountabilities' => 'nullable|string',
            'qualifications_experience' => 'nullable|string',
            'competencies' => 'nullable|string',
            'financial_details' => 'nullable|string',
            'stakeholders_managed' => 'nullable|string',
            'org_structure' => 'nullable|string',

        ]);

        // dd($validated);
        $jobDescription = JobDescription::create($validated);

        // dd($jobDescription);
        $lineManager = User::whereHas('roles', function ($query) {
            $query->where('name', 'line-manager');
        })->where('deptId', $request->department_id)->first();
        // dd( $lineManager);

        $workflow = Workflow::create([
            'user_id' => Auth::user()->id,
            'work_flow_status' => 0,
            'work_flow_completed' => 0,
            'job_description_id' => $jobDescription->id

        ]);


        $input = [
            'work_flow_id' => $workflow->id,
            'forwarded_by' => Auth::user()->id,
            'attended_by' => $lineManager->id,
            'remark' => 'Initiate Requisition',
            'jd_status' => 0,
        ];
        WorkFlowHistory::create($input);

        return redirect()->route('job.description')->with('success', 'Job Description created successfully.');
    }

    public function edit(JobDescription $jobDescription)
    {
        $users = User::with('department')->get();
        $jobTitles = JobTitle::all();
        $departments = Departments::all();
        return view('job_descriptions.edit', compact('jobDescription', 'users', 'jobTitles', 'departments'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        try {
            // Validate the incoming request (only for editable fields)
            $validated = $request->validate([
                // 'employee_type' => 'required|in:existing,new',
                // 'user_id' => ['nullable', 'required_if:employee_type,existing', 'exists:users,id'],
                // 'dept_id' => 'nullable|string',
                // 'job_title' => 'required|string', // "Quos autem totam lab"
                // 'grade_job_holder' => 'nullable|string', // "Quis et et pariatur"
                // 'region_location' => 'nullable|in:Dar es Salaam,Moshi', // "Moshi"
                // 'grade_difference_reason' => 'nullable|string', // "Reprehenderit vitae"
                'purpose' => 'nullable|string',
                'employees_managed' => 'nullable|string',
                'stakeholders_managed' => 'nullable|string',
                'financial_details' => 'nullable|string',
                'accountabilities' => 'nullable|string',
                'qualifications_experience' => 'nullable|string',
                'competencies' => 'nullable|string',
                // 'org_structure_1' => 'required|in:yes,no',
                // 'org_structure_2' => 'required|in:yes,no',
            ]);
            $jobDescription = JobDescription::find($id);
            $workflowHistory = Workflow::where('job_description_id', $id)
                ->first()
                ->workflowHistory()
                ->where('jd_status', 0)
                ->where('attended_by', Auth::user()->id)
                ->first();
            // Combine org_structure_1 and org_structure_2 into org_structure
            // dd($workflowHistory);

            $workflowHistory->update(
                [
                    'jd_status' => 1
                 ]
                );
            // $orgStructure = 'Org Structure 1: ' . $validated['org_structure_1'] . ', Org Structure 2: ' . $validated['org_structure_2'];

            // Prepare data for update (exclude read-only Job Details fields like reports_to)
            $updateData = [
                // 'employee_type' => $validated['employee_type'], // "existing"
                // 'user_id' => $validated['user_id'], // "9"
                // 'dept_id' => $validated['dept_id'] ?? $jobDescription->dept_id,
                // 'job_title' => $validated['job_title'], // "Quos autem totam lab"
                // 'grade_job_holder' => $validated['grade_job_holder'], // "Quis et et pariatur"
                // 'region_location' => $validated['region_location'], // "Moshi"
                // 'grade_difference_reason' => $validated['grade_difference_reason'], // "Reprehenderit vitae"
                'purpose' => $validated['purpose'],
                'employees_managed' => $validated['employees_managed'],
                'stakeholders_managed' => $validated['stakeholders_managed'],
                'financial_details' => $validated['financial_details'],
                'accountabilities' => $validated['accountabilities'],
                'qualifications_experience' => $validated['qualifications_experience'],
                'competencies' => $validated['competencies'],
                // 'org_structure' => $orgStructure,
            ];

            // Update the job description
            if ($jobDescription->update($updateData)) {

                return redirect()->route('job.description')->with('success', 'Job Description updated successfully.');
            }
            // else {
            //     // If the update fails, redirect with a specific error message
            //     return redirect()->back()->with('error', 'Failed to update Job Description. Please check the form and try again.')->withInput();
            // }

        } catch (\Exception $e) {
            // Log the exception for debugging purposes
            \Log::error('Failed to update Job Description: ' . $e->getMessage());

            // If an exception occurs, redirect with an error message
            return redirect()->back()->with('error', 'Failed to update Job Description: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $jobDescription = JobDescription::find($id);
        // dd($jobDescription);
        $jobDescription->load(['user', 'jobTitle', 'department']);
        $users = User::with(['jobTitle', 'department'])->get();
        $jobTitles = JobTitle::all();
        $departments = Departments::all();
        return view('job_descriptions.show', compact('jobDescription', 'users', 'departments', 'jobTitles'));
    }

        public function downloadPDF($id)
    {
        $jobDescription = JobDescription::findOrFail($id);
        $jobDescription->load(['user', 'jobTitle', 'department']);
        $users = User::with(['jobTitle', 'department'])->get();
        $jobTitles = JobTitle::all();
        $departments = Departments::all();

        $pdf = PDF::loadView('job_descriptions.pdf', compact('jobDescription', 'users', 'departments', 'jobTitles'));
        return $pdf->download('job_description_' . $id . '.pdf');
    }
    public function destroy($id)
    {
        $jobDescription = JobDescription::findOrFail($id);
        $jobDescription->delete();

        return redirect()->route('job.description')->with('success', 'Job Description deleted successfully.');
    }
}
