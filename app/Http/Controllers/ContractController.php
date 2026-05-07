<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Contract;
use App\Models\JobTitle;
use App\Models\Department;
use App\Models\Departments;
use App\Models\FixFlexContract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['user', 'department', 'jobTitle'])->get();
        return view('contracts.index', compact('contracts'));
    }

    public function create()
    {
        $users = User::all();
        $departments = Departments::all();
        $jobTitles = JobTitle::all();
        return view('contracts.create', compact('users', 'departments', 'jobTitles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'place_of_recruitment' => 'nullable|string|max:255',
            'duty_station' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'duration' => 'nullable|string|max:255',
            'working_hours' => 'nullable|string|max:255',
            'probation_period' => 'nullable|string|max:255',
            'basic_pay' => 'nullable|string|max:255',
            'total_gross_pay' => 'nullable|string|max:255',
            'medical_insurance_employee' => 'nullable|string|max:255',
            'medical_insurance_employer' => 'nullable|string|max:255',
            'funeral_insurance_eligibility' => 'nullable|string|max:255',
        ]);

        Contract::create($request->all());
        return redirect()->route('contracts.index')->with('success', 'Contract created successfully.');
    }

    public function show($id)
    {
        $contract = Contract::with(['user', 'department', 'jobTitle'])->findOrFail($id);
        return view('contracts.show', compact('contract'));
    }

    public function edit($id)
    {
        $contract = Contract::findOrFail($id);
        $users = User::all();
        $departments = Departments::all();
        $jobTitles = JobTitle::all();
        return view('contracts.edit', compact('contract', 'users', 'departments', 'jobTitles'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'place_of_recruitment' => 'nullable|string|max:255',
            'duty_station' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'duration' => 'nullable|string|max:255',
            'working_hours' => 'nullable|string|max:255',
            'probation_period' => 'nullable|string|max:255',
            'basic_pay' => 'nullable|string|max:255',
            'total_gross_pay' => 'nullable|string|max:255',
            'medical_insurance_employee' => 'nullable|string|max:255',
            'medical_insurance_employer' => 'nullable|string|max:255',
            'funeral_insurance_eligibility' => 'nullable|string|max:255',
        ]);

        $contract = Contract::findOrFail($id);
        $contract->update($request->all());
        return redirect()->route('contracts.index')->with('success', 'Contract updated successfully.');
    }

    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);
        $contract->delete();
        return redirect()->route('contracts.index')->with('success', 'Contract deleted successfully.');
    }



        // === FIXED FLEX ===
public function indexFixedFlex()
{
    $contracts = FixFlexContract::where('type', 'fixed_flex')->get();
    return view('contracts.fixed_flex.index', compact('contracts'));
}

public function createFixedFlex()
{
    $users = User::all();
    $jobTitles = JobTitle::all();
    $departments = Departments::all();

    return view('contracts.fixed_flex.create', compact('users', 'jobTitles', 'departments'));
}

public function storeFixedFlex(Request $request)
{
    $rules = [
        'user_type' => 'required|in:existing,new',
        'department_id' => 'required|exists:departments,id',
        'job_title_id' => 'required|exists:job_titles,id',
        'duty_station' => 'required|string|max:255',
        'duration' => 'required|string|max:255',
        'salary_fixed' => 'required|numeric|min:0',
        'salary_flexible' => 'required|numeric|min:0',
        'working_hours' => 'required|string|max:255',
        'probation' => 'required|string|max:255',
        'contract_date' => 'required|date',
    ];

    if ($request->user_type === 'existing') {
        $rules['user_id'] = 'required|exists:users,id';
    } else {
        $rules['name'] = 'required|string|max:255';
        $rules['date_of_birth'] = 'required|date';
        $rules['gender'] = 'required|in:Male,Female';
        $rules['nationality'] = 'required|string|max:255';
    }

    $validated = $request->validate($rules);

    $validated['type'] = 'Fix-Flex contract';

    FixFlexContract::create($validated);

    return redirect()->route('contracts.fixed_flex.index')->with('success', 'Contract created successfully.');
}


    public function editFixedFlex($id)
    {
        $contract = Contract::where('type', 'fixed_flex')->findOrFail($id);
        return view('contracts.fixed_flex.edit', compact('contract'));
    }

    public function updateFixedFlex(Request $request, $id)
    {
        $contract = Contract::where('type', 'fixed_flex')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // Add other rules as needed
        ]);

        $contract->update($validated);
        return redirect()->route('contracts.fixed_flex.index')->with('success', 'Contract updated.');
    }

    public function destroyFixedFlex($id)
    {
        $contract = Contract::where('type', 'fixed_flex')->findOrFail($id);
        $contract->delete();

        return redirect()->route('contracts.fixed_flex.index')->with('success', 'Contract deleted.');
    }

    // === Consultant ===
    public function indexConsultant()
{
    return view('contracts.consultant.index');
}

public function createConsultant()
{
    return view('contracts.consultant.create');
}

public function storeConsultant(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $validated['type'] = 'consultant';
    Contract::create($validated);

    return redirect()->route('contracts.consultant.index')->with('success', 'Contract created.');
}

public function editConsultant($id)
{
    $contract = Contract::where('type', 'consultant')->findOrFail($id);
    return view('contracts.consultant.edit', compact('contract'));
}

public function updateConsultant(Request $request, $id)
{
    $contract = Contract::where('type', 'consultant')->findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $contract->update($validated);
    return redirect()->route('contracts.consultant.index')->with('success', 'Contract updated.');
}

public function destroyConsultant($id)
{
    $contract = Contract::where('type', 'consultant')->findOrFail($id);
    $contract->delete();

    return redirect()->route('contracts.consultant.index')->with('success', 'Contract deleted.');
}

   // === Volunteer ===
public function indexVolunteer()
{
    return view('contracts.volunteer.index');
}

public function createVolunteer()
{
    return view('contracts.volunteer.create');
}

public function storeVolunteer(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $validated['type'] = 'volunteer';
    Contract::create($validated);

    return redirect()->route('contracts.volunteer.index')->with('success', 'Contract created.');
}

public function editVolunteer($id)
{
    $contract = Contract::where('type', 'volunteer')->findOrFail($id);
    return view('contracts.volunteer.edit', compact('contract'));
}

public function updateVolunteer(Request $request, $id)
{
    $contract = Contract::where('type', 'volunteer')->findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $contract->update($validated);
    return redirect()->route('contracts.volunteer.index')->with('success', 'Contract updated.');
}

public function destroyVolunteer($id)
{
    $contract = Contract::where('type', 'volunteer')->findOrFail($id);
    $contract->delete();

    return redirect()->route('contracts.volunteer.index')->with('success', 'Contract deleted.');
}


   // === OutputBased ===
   public function indexOutputBased()
   {
       return view('contracts.output_based.index');
   }
   
   public function createOutputBased()
   {
       return view('contracts.output_based.create');
   }
   
   public function storeOutputBased(Request $request)
   {
       $validated = $request->validate([
           'name' => 'required|string|max:255',
       ]);
   
       $validated['type'] = 'output_based';
       Contract::create($validated);
   
       return redirect()->route('contracts.output_based.index')->with('success', 'Contract created.');
   }
   
   public function editOutputBased($id)
   {
       $contract = Contract::where('type', 'output_based')->findOrFail($id);
       return view('contracts.output_based.edit', compact('contract'));
   }
   
   public function updateOutputBased(Request $request, $id)
   {
       $contract = Contract::where('type', 'output_based')->findOrFail($id);
   
       $validated = $request->validate([
           'name' => 'required|string|max:255',
       ]);
   
       $contract->update($validated);
       return redirect()->route('contracts.output_based.index')->with('success', 'Contract updated.');
   }
   
   public function destroyOutputBased($id)
   {
       $contract = Contract::where('type', 'output_based')->findOrFail($id);
       $contract->delete();
   
       return redirect()->route('contracts.output_based.index')->with('success', 'Contract deleted.');
   }
   


   // === Volunteer ===
   public function indexExposureReplacement()
{
    return view('contracts.exposure_replacement.index');
}

public function createExposureReplacement()
{
    return view('contracts.exposure_replacement.create');
}

public function storeExposureReplacement(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $validated['type'] = 'exposure_replacement';
    Contract::create($validated);

    return redirect()->route('contracts.exposure_replacement.index')->with('success', 'Contract created.');
}

public function editExposureReplacement($id)
{
    $contract = Contract::where('type', 'exposure_replacement')->findOrFail($id);
    return view('contracts.exposure_replacement.edit', compact('contract'));
}

public function updateExposureReplacement(Request $request, $id)
{
    $contract = Contract::where('type', 'exposure_replacement')->findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $contract->update($validated);
    return redirect()->route('contracts.exposure_replacement.index')->with('success', 'Contract updated.');
}

public function destroyExposureReplacement($id)
{
    $contract = Contract::where('type', 'exposure_replacement')->findOrFail($id);
    $contract->delete();

    return redirect()->route('contracts.exposure_replacement.index')->with('success', 'Contract deleted.');
}

   // === Locum ===
   public function indexLocum()
{
    return view('contracts.locum.index');
}

public function createLocum()
{
    return view('contracts.locum.create');
}

public function storeLocum(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $validated['type'] = 'locum';
    Contract::create($validated);

    return redirect()->route('contracts.locum.index')->with('success', 'Contract created.');
}

public function editLocum($id)
{
    $contract = Contract::where('type', 'locum')->findOrFail($id);
    return view('contracts.locum.edit', compact('contract'));
}

public function updateLocum(Request $request, $id)
{
    $contract = Contract::where('type', 'locum')->findOrFail($id);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $contract->update($validated);
    return redirect()->route('contracts.locum.index')->with('success', 'Contract updated.');
}

public function destroyLocum($id)
{
    $contract = Contract::where('type', 'locum')->findOrFail($id);
    $contract->delete();

    return redirect()->route('contracts.locum.index')->with('success', 'Contract deleted.');
}

      // === GovtIntern ===
      public function indexGovtIntern()
      {
          return view('contracts.govt_intern.index');
      }
      
      public function createGovtIntern()
      {
          return view('contracts.govt_intern.create');
      }
      
      public function storeGovtIntern(Request $request)
      {
          $validated = $request->validate([
              'name' => 'required|string|max:255',
          ]);
      
          $validated['type'] = 'govt_intern';
          Contract::create($validated);
      
          return redirect()->route('contracts.govt_intern.index')->with('success', 'Contract created.');
      }
      
      public function editGovtIntern($id)
      {
          $contract = Contract::where('type', 'govt_intern')->findOrFail($id);
          return view('contracts.govt_intern.edit', compact('contract'));
      }
      
      public function updateGovtIntern(Request $request, $id)
      {
          $contract = Contract::where('type', 'govt_intern')->findOrFail($id);
      
          $validated = $request->validate([
              'name' => 'required|string|max:255',
          ]);
      
          $contract->update($validated);
          return redirect()->route('contracts.govt_intern.index')->with('success', 'Contract updated.');
      }
      
      public function destroyGovtIntern($id)
      {
          $contract = Contract::where('type', 'govt_intern')->findOrFail($id);
          $contract->delete();
      
          return redirect()->route('contracts.govt_intern.index')->with('success', 'Contract deleted.');
      }
      
}
