<?php

namespace App\Http\Controllers;

use App\Models\JobTitle;
use App\Models\Department;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobTitleController extends Controller
{

    public function index()
    {
        $jobTitles = JobTitle::withCount('user')
            ->with(['department' => function ($query) {
                $query->withCount('user');
            }])
            ->orderBy('job_title', 'asc')
            ->get();

        $departments = Departments::withCount('user')
            ->orderBy('dept_name', 'asc')
            ->get();

        return view('job_titles.index', compact('jobTitles', 'departments'));
    }



    public function create()
    {
        $departments = Departments::all();
        $jobTitles = JobTitle::all();
        return view('job_titles.create', compact('departments', 'jobTitles'));
    }

    public function store(Request $request)
    {
        // $request->validate([
        //     'job_title' => 'required|string|max:255',
        //     'deptId' => 'required|exists:departments,id',
        // ]);
        //  if()
        // JobTitle::create($request->all());
        $validator = Validator::make($request->all(), [
            'job_title' => 'required',
            'deptId' => 'required|exists:departments,id',
            'clinical_or_non_clinical' => 'required|in:Clinical,Non-Clinical',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $job = JobTitle::where('job_title', $request->job_title)->first();
        if ($job) {
            return response()->json([
                'status' => 400,
                'message' => 'This Job title exists in the system',
                'data' => $request->all()
            ], 400);
        }

        $job = JobTitle::create([
            'job_title' => $request->input('job_title'),
            'deptId' => $request->input('deptId'),
            'clinical_or_non_clinical' => $request->input('clinical_or_non_clinical'),
        ]);

        return redirect()->route('job_titles.index')->with('success', 'Job Title created successfully.');
    }

    public function edit(JobTitle $jobTitle)
    {
        $departments = Departments::all();
        return view('job_titles.edit', compact('jobTitle', 'departments'));
    }

    public function update(Request $request, JobTitle $jobTitle)
    {
        $request->validate([
            'job_title' => 'required|string|max:255',
            'deptId' => 'required|exists:departments,id',
            'clinical_or_non_clinical' => 'required|in:Clinical,Non-Clinical',
        ]);

        // Update the job title
        $jobTitle->update([
            'job_title' => $request->job_title,
            'deptId' => $request->deptId,
            'clinical_or_non_clinical' => $request->clinical_or_non_clinical,
        ]);

        return redirect()->route('job_titles.index')->with('success', 'Job Title updated successfully.');
    }

    public function destroy(JobTitle $jobTitle)
    {
        if ($jobTitle->user()->count() > 0) {
            return redirect()->route('job_titles.index')->with('error', 'Cannot delete this Job Title because it has assigned users.');
        }

        $jobTitle->delete();
        return redirect()->route('job_titles.index')->with('success', 'Job Title deleted successfully.');
    }
}
