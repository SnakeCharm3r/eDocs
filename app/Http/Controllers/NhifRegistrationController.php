<?php

namespace App\Http\Controllers;

use App\Mail\ApprovalRequestNotification;
use Illuminate\Http\Request;
use App\Models\NhifRegistration;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;

class NhifRegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        return view('nhif_registration.index', compact('user'));
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

        $request->validate([
            // Validation rules
        ]);

        // Create a new NHIF registration record
        $nhifRegistration = NhifRegistration::create([
            'userId' => auth()->id(),
        ]);

        // Create a new Workflow record with the correct NHIF registration ID
        $workflow = new Workflow;
        $workflow->user_id = $user->id;
        $workflow->work_flow_status = 'Pending for approval';
        $workflow->work_flow_completed = 0;
        $workflow->nhif_form = $nhifRegistration->id; // Use the created nhifRegistration ID here
        $workflow->save();

        // Fetch HR users for approval
        $hr_to_approve = User::role('hr')->get();
        foreach ($hr_to_approve as $NHIF) {
            $this->saveWorkflowHistory([
                'work_flow_id' => $workflow->id,
                'forwarded_by' => $user->id,
                'attended_by' => $NHIF->id,
                'status' => '0',
                'remark' => 'NHIF form',
                'attend_date' => Carbon::now()->format('d F Y'),
                'parent_id' => null,
            ]);

            // Prepare request details
            $requestDetails = [
                'forwarded_by' => $user->fname,
                'request' => "NHIF Details Form",
                'requestDate' => Carbon::now()->format('d F Y'),
            ];

            // Send email to each HR user (for pending request)
            foreach ($hr_to_approve as $nhr) {
                $mail = new ApprovalRequestNotification($nhr,$requestDetails);
                $mail->approver = $nhr;
                $mail->requestDetails = $requestDetails;

                Mail::to($nhr->email)->send($mail);
            }
        }

        Alert::success('Successfully.', 'NHIF details added');
        return redirect()->route('request.index')->with('success', 'NHIF details saved successfully.');
    }


    public function saveWorkflowHistory($input){
        return WorkFlowHistory::create($input);
      }




    /**
     * Display the specified resource.
     */
    public function show(NhifRegistration $nhifRegistration)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NhifRegistration $nhifRegistration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NhifRegistration $nhifRegistration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NhifRegistration $nhifRegistration)
    {
        //
    }
}
