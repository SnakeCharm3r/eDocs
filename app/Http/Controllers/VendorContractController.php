<?php

namespace App\Http\Controllers;
use App\Models\CcbrtContract;
use App\Models\User;
use App\Models\CcbrtVendor;
use App\Models\Division;
use App\Models\Departments;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use App\Models\ContractRenewal;
use App\Models\Hec;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;
use App\Mail\ApprovalRequestNotification;
use App\Mail\ContractAddedMail;
use App\Mail\ContractRenewalCreated;
use App\Mail\ReportMail;
use Illuminate\Support\Facades\Storage;

class VendorContractController extends Controller
{

    public function index()
{
    $contracts = CcbrtContract::with(['division', 'department', 'vendor', 'creator'])->get(); 

    // Set default values for all stats
    $totalContracts = $contracts->count();
    $totalValue = $contracts->sum('cost');

    $today = Carbon::now();
    $soonToExpire = $today->copy()->addDays(30);

    // Filter contracts by status
    $expiredContracts = $contracts->where('end_date', '<', $today);
    $soonToExpireContracts = $contracts->whereBetween('end_date', [$today, $soonToExpire]);
    $activeContracts = $contracts->where('end_date', '>', $today);

    // Counts
    $activeContractsCount = $activeContracts->count();
    $expiredContractsCount = $expiredContracts->count();
    $soonToExpireContractsCount = $soonToExpireContracts->count();

    // Financials
    $activeContractsValue = $activeContracts->sum('cost');
    $expiredContractsValue = $expiredContracts->sum('cost');
    $soonToExpireContractsValue = $soonToExpireContracts->sum('cost');

    // Return view with all variables
    return view('vendorcontracts.index', [
        'contracts' => $contracts,
        'activeContracts' => $activeContracts,
        'expiredContracts' => $expiredContracts,
        'soonToExpireContracts' => $soonToExpireContracts,
        'totalContracts' => $totalContracts,
        'totalValue' => $totalValue,
        'activeContractsCount' => $activeContractsCount,
        'activeContractsValue' => $activeContractsValue,
        'expiredContractsCount' => $expiredContractsCount,
        'expiredContractsValue' => $expiredContractsValue,
        'soonToExpireContractsCount' => $soonToExpireContractsCount,
        'soonToExpireContractsValue' => $soonToExpireContractsValue,
        'noContracts' => $contracts->isEmpty()
    ]);
}

    public function create($id = null)
    {
        $contract = null;
        if ($id) {
            $contract = CcbrtContract::with(['division', 'department', 'vendor'])->find($id);
            if (! $contract) {
                return redirect()->route('vendorContract.index')->with('error', 'Contract not found.');
            }
        }

        return view('vendorcontracts.create', compact('contract'));
    }

    public function addNewContract(){

        // return the new contract view
        return view('vendorcontracts.store');
    }

    public function store(Request $request)
    {
        //create and renew contracts
        $user = auth()->user();
        $request->validate([
        'title'=>'required|string|max:255',
        'contract_type'=>'required|string|max:255',
        'vendor_id'=>'nullable|integer|required_without:department_id',
        'department_id'=>'nullable|integer|required_without:vendor_id',
        'division_id'=> 'nullable|integer',
        'cost'=> 'required|numeric',
        'duration_months'=> 'required|integer',
        'status'=> 'required|string|max:255',
        'file_path'=> 'nullable|mimes:pdf|max:2048', // max 2MB
]);

        $contract = CcbrtContract ::create([
            'title'=>$request->title,
            'contract_type'=>$request->contract_type,
            'vendor_id'=>$request->vendor_id,
            'division_id'=>$request->division_id,
            'department_id'=>$request->department_id,
            'cost'=>$request->cost,
            'duration_months'=>$request->duration_months,
            'end_date'=>$request->end_date,
            'created_by'=>Auth::id(),
        ]);

        //handle file upload
        if($request->hasFile('file_path')){
            $file = $request->file('file_path');
            $filename = time().'_'.$file->getClientOriginalName();
            $filePath = $file->storeAs('contracts', $filename, 'public');
            $contract->file_path = '/storage/'.$filePath;
            $contract->save();
        }

        //Find the HEC member for this users department
        $HecMember = $user->department->Hec ?? null;

        
        //handle workflows creation
        $workflow = Workflow::create([
        'user_id' => $user->id,
        'work_flow_status' => 'sent to approval',
        'work_flow_completed' => 2,
        'ccbrt_contract_id' => $contract->id,
       ]);

         //making a new Workflow history
         $history = WorkFlowHistory::create([
            'work_flow_id' => $workflow->id,
            'remark' => 'Ccbrt Contract forwarded for approval',
            'forwarded_by' => Auth::user()->id,
            'attended_by' =>Auth::user()->id,
            'status' =>1,//pending
            'created_at' => Carbon::now(),
        ]);

        return redirect()->route('vendorContract.index')->with('success', 'Contract added successfully!');

        return redirect()->back()->withErrors('Error adding contract. Please try again.');
    }

    public function approve($id) {

        //approve created or renewed contracts
        $contract = CcbrtContract::findOrFail($id);
        $workflowHistory = $contract->workflow->histories()
        ->where('attended_by', Auth::id())
        ->where('status', 0) 
        ->latest()
        ->first();

        $role = Auth::user()->getRoleNames()[0];

        // // Notify relevant parties about the approval
        // Mail::to($user->email)->send(new ApprovalRequestNotification($contract));

        return redirect()->route('vendorContract.index')->with('success', 'Contract approved successfully!');

}
    public function approveContract(Request $request, $id)
    {
        //approve duplicate contracts
        $contract = CcbrtContract::findOrFail($id);
        $workflowHistory = $contract->workflow->histories()
            ->where('attended_by', Auth::id())
            ->where('status', 0) // pending
            ->latest()
            ->first();

        if ($workflowHistory) {
            $workflowHistory->status = 1; // approved
            $workflowHistory->remark = 'Approved by ' . Auth::user()->name;
            $workflowHistory->save();

            // Update the workflow status if needed
            $workflow = $contract->workflow;
            $workflow->work_flow_status = 'approved';
            $workflow->work_flow_completed = 1; // Mark as completed
            $workflow->save();

            return redirect()->route('vendorContract.index')->with('success', 'Contract approved successfully!');
        }

        return redirect()->route('vendorContract.index')->withErrors('Error approving contract. Please try again.');
    }


    public function rejectContract(Request $request, $id)
    {
        $contract = CcbrtContract::findOrFail($id);

        // Find the workflow associated with this contract
        $workflowHistory = $contract->workflow->histories()
            ->where('attended_by', Auth::id())
            ->where('status', 0) // pending
            ->latest()
            ->first();

        if ($workflowHistory) {
            $workflowHistory->status = 2; // rejected
            $workflowHistory->remark = 'Rejected by ' . Auth::user()->name;
            $workflowHistory->save();

            // Update the workflow status if needed
            $workflow = $contract->workflow;
            $workflow->work_flow_status = 'rejected';
            $workflow->work_flow_completed = 1; // Mark as completed
            $workflow->save();

            return redirect()->route('vendorContract.index')->with('success', 'Contract rejected successfully!');
        }

        return redirect()->route('vendorContract.index')->withErrors('Error rejecting contract. Please try again.');
    }

    public function contractApprovalIndex()
{
    //SHOW all approved contracts
    $contracts = CcbrtContract::with([
        'workflows.histories',
        'workflows.user'       
    ])->get();

    foreach ($contracts as $contract) {
        // Get the current active workflow step (status = pending)
        $currentApproval = $contract->workflows
            ->where('work_flow_status', 'pending')
            ->first();

        $currentApprover = $currentApproval?->user;

        // Next approver could be the next workflow in line
        $nextApproval = $contract->workflows
            ->where('work_flow_status', 'queued')
            ->first();

        $nextApprover = $nextApproval?->user;

        // You can attach these for display
        $contract->current_approver = $currentApprover;
        $contract->next_approver = $nextApprover;
    }

    return view('vendorcontracts.approveContract', compact('contracts'));
}

// ---------------------------------------------------------------------------------------------------------------------------------//

    public function uploadContract(){

        //return the upload contract view
        $divisions = Division::all();
        $departments = Departments::all();
        $vendors = CcbrtVendor::all();  
        return view('vendorcontracts.uploadContract')->with([
            'divisions' => $divisions,
            'departments' => $departments,
            'vendors' => $vendors,
        ]);
    }
    
public function upload(Request $request)
{
    try {
        // Validate request inputs with custom messages
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'contract_type' => 'required|string|max:255',
            'vendor_id' => 'required|exists:ccbrt_vendors,id', 
            'division_id' => 'required|exists:divisions,id',
            'currency' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|string|in:draft,active,expired,terminated,soon_to_expire',
            'cost' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'notice_period_months' => 'nullable|integer',
            'creation_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:creation_date',
            'impact_if_not_requested' => 'required|string|in:Low,Medium,High',
            'likelihood_rating' => 'required|string|in:Low,Medium,High',
            'renewal_status' => 'required|string|in:renewed,not_renewed,pending',
            'file_path' => 'required|file|max:51200', // 50MB
        ], [
            // Custom error messages
            'title.required' => 'The contract document name is required.',
            'title.max' => 'The contract name may not be greater than 255 characters.',
            'vendor_id.required' => 'Please select a vendor.',
            'vendor_id.exists' => 'The selected vendor is invalid.',
            'file_path.required' => 'Please upload a contract file.',
            'file_path.max' => 'The contract file must not exceed 50MB.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ]);

        // Additional custom validation
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $allowedMimes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = $file->getClientOriginalExtension();
            
            if (!in_array(strtolower($fileExtension), $allowedMimes)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['file_path' => 'Invalid file type. Allowed types: PDF, Word, Excel, Images.']);
            }
        }

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('contracts', $fileName, 'public');
        }

        // Create contract
        $contract = CcbrtContract::create([
            'title' => $validated['title'],
            'contract_type' => $validated['contract_type'],
            'vendor_id' => $validated['vendor_id'],
            'division_id' => $validated['division_id'],
            'currency' => $validated['currency'],
            'department_id' => $validated['department_id'],
            'status' => $validated['status'],
            'cost' => $validated['cost'],
            'duration_months' => $validated['duration_months'],
            'notice_period_months' => $validated['notice_period_months'],
            'creation_date' => $validated['creation_date'],
            'end_date' => $validated['end_date'],
            'impact_if_not_requested' => $validated['impact_if_not_requested'],
            'likelihood_rating' => $validated['likelihood_rating'],
            'renewal_status' => $validated['renewal_status'],
            'file_path' => $filePath,
            'created_by' => auth()->id(),
        ]);

        // Eager load relationships
        $contract->load(['vendor', 'department', 'creator']);

        // Get recipients based on department
        $lineManagers = User::role('line-manager')->where('deptId', $contract->department_id)->get();
        $procurementOfficers = User::role('procurement officer')->get();
        $recipients = $lineManagers->merge($procurementOfficers)->pluck('email')->unique();

        // Send emails
        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new ContractAddedMail($contract));
                \Log::info('Contract email sent successfully to: ' . $email);
            } catch (\Exception $e) {
                \Log::error('Failed to send contract email: ' . $e->getMessage(), [
                    'email' => $email,
                    'exception' => $e
                ]);
            }
        }

        return redirect()->route('vendorContract.index')
                         ->with('success', 'Contract uploaded successfully. Check logs for email delivery status.');

    } catch (\Illuminate\Validation\ValidationException $e) {
        // This will automatically redirect back with errors and input
        throw $e;
    } catch (\Exception $e) {
        \Log::error('Contract upload failed: ' . $e->getMessage());
        return redirect()->back()
                         ->withInput()
                         ->with('error', 'Failed to upload contract. Please try again.');
    }
}

//----------------------------------------------------------------------------------------------------------------------------------//


// This shows you the contract renewal page
public function makeContract()

    {
        //return the contracts page for all created contracts
        $vendors = CcbrtVendor::all();
        $divisions = Division::all();
        $departments = Departments::all();
        $contracts = CcbrtContract::all();
        return view('vendorcontracts.makeContract', compact('vendors','departments','divisions','contracts'));
    }

 // This part uploads a new contract renewal process
public function InitiateContractRenewal(Request $request)
{
    try {
        // 1. Validate the request
        $validated = $request->validate([
            'contract_id' => 'required|exists:ccbrt_contracts,id',
            'vendor_id' => 'nullable|exists:ccbrt_vendors,id',
            'vendor_option' => 'required|in:existing,new',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'required|exists:divisions,id',
            'vendor_review' => 'nullable|integer|min:1|max:10',
            'contract_type' => 'required|string',
            'category' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'status' => 'required|string|in:New,Renewal,Extension',
            'likelihood_rating' => 'required|string',
            'impact_if_not_requested' => 'required|string',
            'overall_risk' => 'required|string',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'service_requirements_text' => 'nullable|string',
        ]);

        \Log::info('Validation passed for Contract Renewal', $validated);

        // 2. Handle service requirements text
        $serviceRequirements = $request->input('service_requirements_text', null);

        $filePath = null;
        if ($request->hasFile('contract_file')) {
            $filePath = $request->file('contract_file')->store('contract_renewals', 'public');
            \Log::info('Contract file uploaded successfully to: ' . $filePath);
        }

        // 4. Create Contract Renewal record
        $contractRenewal = ContractRenewal::create([
            'contract_id' => $validated['contract_id'],
            'vendor_id' => $validated['vendor_id'] ?? null,
            'vendor_option' => $validated['vendor_option'],
            'department_id' => $validated['department_id'] ?? null,
            'division_id' => $validated['division_id'],
            'vendor_review' => $validated['vendor_review'] ?? null,
            'service_requirements_text' => $serviceRequirements,
            'contract_type' => $validated['contract_type'],
            'category' => $validated['category'],
            'cost' => $validated['cost'],
            'duration_months' => $validated['duration_months'],
            'status' => $validated['status'],
            'likelihood_rating' => $validated['likelihood_rating'],
            'impact_if_not_requested' => $validated['impact_if_not_requested'],
            'overall_risk' => $validated['overall_risk'],
            'created_by' => Auth::id(),
            'service_requirements_file' => $filePath, 
        ]);

        \Log::info('Contract Renewal created with ID: ' . $contractRenewal->id);

         $workflow = Workflow::create([
            'user_id' => Auth::id(),
            'work_flow_status' => 'sent to approval',
            'work_flow_completed' => 0,
            'contract_renewal_id' => $contractRenewal->id,
        ]);

        $workflowHistory = WorkFlowHistory::create([
            'work_flow_id' => $workflow->id,
            'remark' => 'Contract Renewal forwarded for approval',
            'forwarded_by' => Auth::user()->id,
            'attended_by' => Auth::user()->id,
            'status' => 1, // pending
            'created_at' => Carbon::now(),
        ]);

        //Find next approvers (HEC)
       $nextApprovers = $this->findNextApprover($workflow, 'line-manager');

       if ($nextApprovers) {
             foreach ($nextApprovers as $approver) {
                WorkFlowHistory::create([
                'work_flow_id' => $workflow->id,
                'forwarded_by' => Auth::id(), // Line Manager who submitted
                'attended_by' => $approver->id,
                'status' => 1, // pending
                'remark' => "Request forwarded to HEC for approval",
        ]);
    }

         // Update workflow status to reflect next approver
        $workflow->update([
        'work_flow_status' => 'sent to HEC',
    ]);
        \Log::info('Workflow created for Contract Renewal ID: ' . $contractRenewal->id);

        return redirect()->route('request.index')->with('success', 'Contract Renewal Request submitted successfully.');
    }
    } catch (\Exception $e) {
        \Log::error('Error creating Contract Renewal: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return redirect()->back()->with('error', 'Error submitting request: ' . $e->getMessage());
    }
}
    
    //Map Hec level name to the approvers role and department
    private function findNextApprover(Workflow $workflow, $currentRole)
{
    // Define the approval flow
    $approvalFlow = [
        'line-manager' => 'hec',
        'hec' => 'procurement-officer',
    ];

    // Find the next role
    $nextRoleKey = $approvalFlow[$currentRole] ?? null;

    if (!$nextRoleKey) {
        return null; // No next approver — workflow complete
    }

    // Get next approvers
    if ($nextRoleKey === 'hec') {
        $hecLevel = Departments::where('id', $workflow->contractRenewal->department_id)
            ->value('hec_id');

        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['coo', 'cms', 'cfo', 'chrdo','ceo']);
        })->get();
    }

    return User::role($nextRoleKey)->get();
}

    //Contract renewal index method
    public function ContractRenewalIndex($id)
{
    $user = auth()->user();
 
    $contractRenewal = ContractRenewal::with(['contract', 'vendor', 'division', 'department', 'user'])
        ->findOrFail($id);

    return view('vendorcontracts.approveMakeContract', compact('contractRenewal', 'user'));
}

//approve contract renewal
public function ApproveRenewal(Request $request, $id){
    
    $contractRenewal = ContractRenewal::with(['histories','contract','division','department'])
    ->findOrFail($id);
    $workflow = $contractRenewal->workflow;

    // Find the current approver’s active step
    $currentStep = $workflow->histories()
        ->where('attended_by', Auth::id())
        ->where('status', 1) // pending
        ->latest()
        ->first();

    if (!$currentStep) {
        return back()->withErrors('No pending approval found for you.');
    }

    $currentStep->status = 2; // approved
    $currentStep->who_approve = Auth::id();
    $currentStep->attend_date = now();
    $currentStep->remark = 'Approved by ' . Auth::user()->name;
    $currentStep->save();

    $nextStep = $workflow->histories()
        ->where('id', '>', $currentStep->id)
        ->where('status', 0) // unsubmitted
        ->orderBy('id', 'asc')
        ->first();

    if ($nextStep) {
        $nextStep->status = 1; // now pending
        $nextStep->forwarded_by = Auth::id();
        $nextStep->save();

        $workflow->work_flow_status = 'pending';
    } else {
        // Final approver: mark workflow complete
        $workflow->work_flow_status = 'approved';
        $workflow->work_flow_completed = 1;
    }

    $workflow->save();

    return redirect()->route('vendorContract.index')->with('success', 'Approval submitted successfully.');
}

//reject contract renewal
public function rejectRenewal(Request $request, $id)
{
    $request->validate([
        'rejection_reason' => 'required|string|max:1000',
    ]); 
    $contractRenewal = ContractRenewal::findOrFail($id);
    $workflow = $contractRenewal->workflow;

    $currentStep = $workflow->histories()
        ->where('attended_by', Auth::id())
        ->where('status', 1) // pending
        ->latest()
        ->first();

    if (!$currentStep) {
        return back()->withErrors('No pending approval found for you.');
    }
    $currentStep->status = 3; // rejected   
    $currentStep->who_approve = Auth::id();
    $currentStep->attend_date = now();
    $currentStep->remark = $request->input('rejection_reason');
    $currentStep->save();

    $workflow->work_flow_status = 'rejected';
    $workflow->work_flow_completed = 3;
    $workflow->save();
    return redirect()->route('contractRenewal.index')->with('success', 'Contract Renewal rejected successfully.');
}

//view to document download
public function downloadPDF($id)
{
    $contractRenewal = ContractRenewal::with(['vendor', 'division', 'user'])->findOrFail($id);

    $pdf = Pdf::loadView('pdf.contractRenewal', compact('contractRenewal'))
              ->setPaper('a4', 'portrait');

    $fileName = 'Contract_Renewal_' . $contractRenewal->id . '.pdf';

    return $pdf->download($fileName);
}

    public function update(Request $request, $id)
    {
        $contract = CcbrtContract::findOrFail($id);

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'vendor_id'     => 'nullable|exists:ccbrt_vendors,id',
            'cost'          => 'nullable|numeric',
            'creation_date' => 'nullable|date',
            'end_date'      => 'nullable|date',
            'duration_months'=> 'nullable|integer',
            'currency'      => 'nullable|string|max:10',
            'status'        => 'nullable|string|max:50',
            'file_path'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,gif,doc,docx|max:5120', // allow images/docs, 5MB
            'remove_file'   => 'nullable|in:0,1',
        ]);

        // assign simple fields
        $contract->title = $validated['title'];
        $contract->vendor_id = $validated['vendor_id'] ?? $contract->vendor_id;
        $contract->cost = $validated['cost'] ?? $contract->cost;
        $contract->creation_date = $validated['creation_date'] ?? $contract->creation_date;
        $contract->end_date = $validated['end_date'] ?? $contract->end_date;
        $contract->duration_months = $validated['duration_months'] ?? $contract->duration_months;
        $contract->currency = $validated['currency'] ?? $contract->currency;
        $contract->status = $validated['status'] ?? $contract->status;

        // If client requested to remove existing file, delete it and clear path
        if ($request->input('remove_file') == '1') {
            if ($contract->file_path && Storage::disk('public')->exists($contract->file_path)) {
                Storage::disk('public')->delete($contract->file_path);
            }
            $contract->file_path = null;
        }

        // handle file upload if provided (replace existing file)
        if ($request->hasFile('file_path')) {
            // delete previous stored file to avoid orphaned files
            if ($contract->file_path && Storage::disk('public')->exists($contract->file_path)) {
                Storage::disk('public')->delete($contract->file_path);
            }
            $file = $request->file('file_path');
            $path = $file->store('contracts', 'public');
            $contract->file_path = $path;
        }

        // who updated
        $contract->updated_by = Auth::id() ?? $contract->updated_by;

        $contract->save();

        return redirect()->route('vendorContract.index')->with('success', 'Contract updated successfully.');
    }

}