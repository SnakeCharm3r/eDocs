<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Contract;
use App\Models\JobTitle;
use Barryvdh\DomPDF\PDF;
use App\Models\Departments;
use Illuminate\Http\Request;
use App\Models\ContractTemplate;

class FixedFlexContractController extends Controller
{
    //
    public function create()
    {
        $template = ContractTemplate::where('type', 'Fix-Flex contract')->first();
        $users = User::all();
        $departments = Departments::all();
        $jobTitles = JobTitle::all();

        return view('contracts.fixed-flex.create', compact('template', 'users', 'departments', 'jobTitles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_type' => 'required|in:existing,new',
            'user_id' => 'required_if:user_type,existing|exists:users,id',
            'name' => 'required_if:user_type,new|string|max:255',
            'date_of_birth' => 'required_if:user_type,new|date',
            'gender' => 'required_if:user_type,new|in:Male,Female',
            'nationality' => 'required_if:user_type,new|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'job_title_id' => 'required|exists:job_titles,id',
            'duty_station' => 'required|string|max:255',
            'duration' => 'required|string|max:255',
            'salary_fixed' => 'required|numeric',
            'salary_flexible' => 'required|numeric',
            'working_hours' => 'required|string|max:255',
            'probation' => 'required|string|max:255',
            'contract_date' => 'required|date',
        ]);

        // Handle user creation if new user
        if ($validated['user_type'] === 'new') {
            $user = User::create([
                'fname' => $validated['name'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'nationality' => $validated['nationality'],
                // Add other required user fields
            ]);
            $validated['user_id'] = $user->id;
        }

        // Get the Fix-Flex template
        $template = ContractTemplate::where('type', 'Fix-Flex contract')->first();

        // Create the contract
        $contract = $template->contracts()->create([
            'user_id' => $validated['user_id'],
            'start_date' => now(),
            'end_date' => now()->addYear(), // Adjust based on duration
            'status' => 'active',
        ]);

        // Create contract fields
        $fields = [
            'employee_name' => $validated['user_type'] === 'existing' 
                ? User::find($validated['user_id'])->fullName()
                : $validated['name'],
            'date_of_birth' => $validated['user_type'] === 'existing'
                ? User::find($validated['user_id'])->date_of_birth
                : $validated['date_of_birth'],
            'gender' => $validated['user_type'] === 'existing'
                ? User::find($validated['user_id'])->gender
                : $validated['gender'],
            'nationality' => $validated['user_type'] === 'existing'
                ? User::find($validated['user_id'])->nationality
                : $validated['nationality'],
            'job_title' => JobTitle::find($validated['job_title_id'])->job_title,
            'duty_station' => $validated['duty_station'],
            'duration' => $validated['duration'],
            'salary_fixed' => $validated['salary_fixed'],
            'salary_flexible' => $validated['salary_flexible'],
            'working_hours' => $validated['working_hours'],
            'probation' => $validated['probation'],
            'contract_date' => $validated['contract_date'],
        ];

        foreach ($fields as $fieldName => $value) {
            $templateField = $template->templateFields()
                ->where('field_name', $fieldName)
                ->first();

            if ($templateField) {
                $contract->contractFields()->create([
                    'template_field_id' => $templateField->id,
                    'value' => $value,
                ]);
            }
        }

        return redirect()->route('contracts.fixed-flex.show', $contract)
            ->with('success', 'Contract created successfully');
    }

    public function show($id)
    {
        $contract = Contract::with(['contractFields', 'user', 'contractTemplate'])->findOrFail($id);
        $document = $contract->generateDocument();

        return view('contracts.fixed-flex.show', compact('contract', 'document'));
    }

    public function download($id)
{
    $contract = Contract::findOrFail($id);
    $document = $contract->generateDocument();
    
    $pdf = PDF::loadHTML($document);
    return $pdf->download("contract_{$contract->id}.pdf");
}
}
