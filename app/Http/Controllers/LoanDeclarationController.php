<?php

namespace App\Http\Controllers;

use App\Models\LoanDeclaration;
use App\Models\User;
use App\Models\Workflow;
use App\Mail\ApprovalRequestNotification;
use App\Models\WorkFlowHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;

class LoanDeclarationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        return view('hslb_loan.index', compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        // Validate the request data
        $validated = $request->validate([
            'form_iv_index' => 'nullable|string|max:255',
            'has_loan' => 'required|in:Yes,No',
        ]);

        // Create a new LoanDeclaration record
        $loanDeclaration = LoanDeclaration::create([
            'userId' => auth()->id(),
            ...$validated,
        ]);

        // Create a new workflow for the loan declaration
        $workflow = new Workflow;
        $workflow->user_id = $user->id;
        $workflow->work_flow_status = 'Pending for approval';
        $workflow->work_flow_completed = 0;
        $workflow->heslb_form = $loanDeclaration->id; // Use the LoanDeclaration ID here
        $workflow->save();

        // Send workflow history to HR for approval
        $hr_to_approve = User::role('hr')->get();
        foreach ($hr_to_approve as $heslbhr) {
            $this->saveWorkflowHistory([
                'work_flow_id' => $workflow->id,
                'forwarded_by' => $user->id,
                'attended_by' => $heslbhr->id,
                'status' => '0',
                'remark' => 'Heslb Detail form',
                'attend_date' => Carbon::now()->format('d F Y'),
                'parent_id' => null,
            ]);

            // Prepare request details for email
            $requestDetails = [
                'forwarded_by' => $user->fname,
                'request' => "Heslb Details Form",
                'requestDate' => Carbon::now()->format('d F Y'),
            ];

            // Send email to each HR user (for pending request)
            foreach ($hr_to_approve as $hr) {
                $mail = new ApprovalRequestNotification($hr, $requestDetails);
                $mail->approver = $hr;
                $mail->requestDetails = $requestDetails;

                Mail::to($hr->email)->send($mail);
            }
        }

        // Display success alert and redirect
        Alert::success('saved successfully.', 'HESLB loan declaration form added');
        return redirect()->route('request.index')->with('success', 'HESLB loan declaration form saved successfully.');
    }

      public function saveWorkflowHistory($input){
        return WorkFlowHistory::create($input);
      }

    /**
     * Display the specified resource.
     */
    public function show(LoanDeclaration $loanDeclaration)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LoanDeclaration $loanDeclaration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LoanDeclaration $loanDeclaration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LoanDeclaration $loanDeclaration)
    {
        //
    }
}
