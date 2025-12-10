<?php

namespace App\Http\Controllers;

use App\Mail\ApprovalRequestNotification;
use App\Models\BankDetail;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;

class BankDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        return view('bank_forms.index', compact('user'));
    }


    public function create()
    {
        return view('bank_details_form');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validator = $request->validate([
            'bank_name' => 'required|string|max:255',
            'branch_name' => 'required|string|max:255',
            'branch_address' => 'nullable|string|max:255',
            'account_name' => 'required|string|max:255',
            'bank_mobile_number' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'swift_code' => 'nullable|string|max:255',
        ]);

        $bankDetails = BankDetail::create([
            'bank_name' => $request->input('bank_name' ),
            'branch_name' => $request->input('branch_name' ),
            'branch_address' => $request->input('branch_address' ),
            'account_name' => $request->input('account_name' ),
            'bank_mobile_number' => $request->input('bank_mobile_number' ),
            'account_number' => $request->input('account_number' ),
            'swift_code' => $request->input('swift_code' ),
            'userId' => Auth::id(),
            ]);

            // dd($bankDetails);

        $workflow = $this->saveWorkflow([
            'user_id' => Auth::user()->id,
            'bank_form' => $bankDetails->id,
            'work_flow_status' => 'sent to approval',
            'work_flow_completed' => 0,
        ]);
           //  dd( $workflow );
          // Fetch all HR users
        $hr_to_approve = User::role('hr')->get();
         foreach($hr_to_approve as $HR){
            $this->saveWorkflowHistory([
            'work_flow_id' => $workflow->id,
            'forwarded_by' => $user->id,
            'attended_by' => $HR->id,
            'status' => '0',
            'remark' => 'Bank Details',
            'attend_date' => Carbon::now()->format('d F Y'),
            'parent_id' => null,
            ]);

             // Prepare request details
          $requestDetails = [
            'forwarded_by' => $user->fname,
            'request' => "Bank Details Form",
            'requestDate' => Carbon::now()->format('d F Y'),
          ];

           // Send email to each HR user (for pending request)
           foreach ($hr_to_approve as $hr) {
            $mail = new ApprovalRequestNotification($hr,$requestDetails);
            $mail->approver = $hr;
            $mail->requestDetails = $requestDetails; // Pass the requestDetails including 'forwarded_by'

            Mail::to($hr->email)->send($mail);
          }
         }

        Alert::success('saved.', 'Bank details saved successfully');
        return redirect()->route('request.index')->with('success', 'Bank details saved successfully.');
    }

      public function saveWorkflowHistory($input){
        return WorkFlowHistory::create($input);
      }

      public function saveWorkflow($input)
    {
        // dd($input);
        return Workflow::create($input);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return view('bank_details_form');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
