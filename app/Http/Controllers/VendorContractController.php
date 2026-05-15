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
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\ApprovalRequestNotification;
use App\Mail\ContractAddedMail;
use App\Mail\ContractRenewalCreated;
use App\Mail\ReportMail;

class VendorContractController extends Controller
{

    public function index()
{
    $contracts = ccbrtContract::with(['division', 'department', 'vendor', 'creator'])->get(); 

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

    public function create(){
        
        // return the create contract view
        return view('vendorcontracts.create');
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
    // Validate request inputs
    $request->validate([
        'title' => 'required|string|max:255',
        'contract_type' => 'required|string|max:255',
        'vendor_id' => 'required|exists:ccbrt_vendors,id', 
        'division_id' => 'required|exists:divisions,id',
        'currency' => 'required|string|max:255',
        'department_id' => 'required|exists:departments,id',
        'status' => 'required|string|in:draft,active,expired,terminated',
        'cost' => 'required|numeric|min:0',
        'duration_months' => 'required|integer|min:1',
        'notice_period_months' => 'nullable|integer',
        'creation_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:creation_date',
        'impact_if_not_requested' => 'required|string|in:Low,Medium,High',
        'likelihood_rating' => 'required|string|in:Low,Medium,High',
        'renewal_status' => 'required|string|in:renewed,not_renewed,pending',
        'file_path' => 'required|file|max:10240',
    ]);
    try {
        // Handle file upload
        $filePath = null;
        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('contracts', $fileName, 'public');
        }

        // Create contract
        $contract = CcbrtContract::create([
            'title' => $request->title,
            'contract_type' => $request->contract_type,
            'vendor_id' => $request->vendor_id,
            'division_id' => $request->division_id,
            'currency' => $request->currency,
            'department_id' => $request->department_id,
            'status' => $request->status,
            'cost' => $request->cost,
            'duration_months' => $request->duration_months,
            'notice_period_months' => $request->notice_period_months,
            'creation_date' => $request->creation_date,
            'end_date' => $request->end_date,
            'impact_if_not_requested' => $request->impact_if_not_requested,
            'likelihood_rating' => $request->likelihood_rating,
            'renewal_status' => $request->renewal_status,
            'file_path' => $filePath,
            'created_by' => auth()->id(),
        ]);

        // Eager load relationships: vendor, department, creator
        $contract->load(['vendor', 'department', 'creator']);

        // Get recipients based on department
        $lineManagers = User::role('line-manager')->where('deptId', $contract->department_id)->get();
        $procurementOfficers = User::role('procurement officer')->get();
        $recipients = $lineManagers->merge($procurementOfficers)->pluck('email')->unique();

        // Send emails individually and log success/failure
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

    } catch (\Exception $e) {
        \Log::error('Contract upload failed: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Failed to upload contract. Check logs for details.');
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

        Workflow::create([
            'user_id' => Auth::id(),
            'work_flow_status' => 'sent to approval',
            'work_flow_completed' => 0,
            'contract_renewal_id' => $contractRenewal->id,
        ]);

        \Log::info('Workflow created for Contract Renewal ID: ' . $contractRenewal->id);

        return redirect()->back()->with('success', 'Contract Renewal Request submitted successfully.');

    } catch (\Exception $e) {
        \Log::error('Error creating Contract Renewal: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());

        return redirect()->back()->with('error', 'Error submitting request: ' . $e->getMessage());
    }
}
}


