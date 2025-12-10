<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\IDCards;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkFlowHistory;
use App\Mail\ApprovalRequestNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use RealRashid\SweetAlert\Facades\Alert;

class IDCardController extends Controller
{
    public function index()
    {
        //showing the IDCard stuff goes here
    }

    //show the form on create
    public function create()
    {
    return view("ID-Card.IDCard");

    }

    //storing data into the database
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'user_id' => 'nullable|string',
        ]);  
        //unneccessary validation as the user_id is already set to the authenticated user;

        $idCard = IDCards::create([
            'user_id' =>auth()->id(),
        ]);

        $workflow = new Workflow;
        $workflow->user_id = $user->id;
        $workflow->work_flow_status = 'Pending for approval';
        $workflow->work_flow_completed = 0;
        $workflow->id_form = $idCard->id;
        $workflow->save();

        $hr_to_approve = User::role('hr')->get();
        foreach($hr_to_approve as $HR){
            $this->saveWorkflowHistory([
                'work_flow_id' => $workflow->id,
                'forwarded_by' => $user->id,
                'attended_by' => $HR->id,
                'status' => '0',
                'remark' => 'ID Card Form',
                'attend_date' => Carbon::now()->format('d F Y'),
                'parent_id' => null,
            ]);

               // Prepare request details
        $requestDetails = [
            'forwarded_by' => $user->fname,
            'request' => "ID Card  Form",
            'requestDate' => Carbon::now()->format('d F Y'),
        ];

          // Send email to each HR user (for pending request)
        foreach ($hr_to_approve as $hr) {
            $mail = new ApprovalRequestNotification($hr,$requestDetails);
            // $mail->approver = $hr;
            // $mail->requestDetails = $requestDetails;
            Mail::to($hr->email)->send($mail);
        }

        }
        Alert::success('success', 'ID Card Request submitted successfully!');
        return redirect()->route('request.index')->with('success', 'ID Card Request submitted successfully!');
    }

    public function saveWorkflow($input){
    return Workflow::create($input);
    }

    public function saveWorkflowHistory($input){
        return WorkFlowHistory::create($input);
    }



    public function show(string $id)
    {
        //
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


}
