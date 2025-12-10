<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\User;
use App\Models\Remark;
use App\Models\Workflow;
use Illuminate\Http\Request;
use App\Models\ClearanceForm;
use App\Models\PrivilegeLevel;
use App\Models\HMISAccessLevel;
use App\Models\WorkFlowHistory;
use App\Models\IctAccessResource;
use App\Models\NhifQualification;
use App\Models\Clearance_work_flow;
use App\Models\Clearance_work_flow_history;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\Validator;

class RequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index1()
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login'); // Ensure the user is authenticated
            }

            $userId = Auth::user()->id;

            $form = Workflow::where('user_id', $userId)->get();
            // Initialize an empty array to store histories for each form
            $histories = [];
            // Fetch histories for each form
            foreach ($form as $aform) {
                $history = WorkFlowHistory::where('work_flow_id', $aform->id)->get();
                $histories[$aform->id] = $history; // Store histories keyed by form ID
            }

            // dd($form, $histories);
            return view('myrequest.index', compact('form', 'histories'));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function index()
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login');
            }

            $userId = Auth::user()->id;

            // Fetch only NON-requisition, NON-locum, NON-oncall Workflow Forms
            // Locum and on-call requests have their own separate review areas
            $form = Workflow::where('user_id', $userId)
                ->whereNull('requisition_id') // Exclude requisitions
                ->whereNull('locum_request_id') // Exclude locum requests
                ->whereNull('on_call_request_id') // Exclude on-call requests
                ->orderBy('created_at', 'desc') // Most recent first
                ->get();

            $histories = [];
            foreach ($form as $aform) {
                $history = WorkFlowHistory::where('work_flow_id', $aform->id)->get();
                $histories[$aform->id] = $history;
            }

            // Fetch and sort Clearance Forms
            $clearForm = Clearance_work_flow::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $clearHistories = [];
            foreach ($clearForm as $exit) {
                $clearHistory = Clearance_work_flow_history::where('work_flow_id', $exit->id)->get();
                $clearHistories[$exit->id] = $clearHistory;
            }

            return view('myrequest.index', compact(
                'form',
                'histories',
                'clearForm',
                'clearHistories'
            ));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function getClear()
    {
        try {
            if (!Auth::check()) {
                return redirect()->route('login');
            }
            $userId = Auth::user()->id;
            $clearForm = Clearance_work_flow::where('user_id', $userId)->get();

            $clearHistories = [];
            foreach ($clearForm as $exit) {
                $clearHistory = Clearance_work_flow_history::where('work_flow_id', $exit->id)->get();
                $clearHistories = [$exit->id] = $clearHistory;
            }

            return view('myrequest.index', compact('clearForm', 'clearHistories'));
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(string $id)
    {

        $user = Auth::user();
        $qualifications = NhifQualification::where('delete_status', 0)->get();
        $privileges = PrivilegeLevel::where('delete_status', 0)->get();
        $hmis = HMISAccessLevel::where('delete_status', 0)->get();
        $form = Workflow::findOrFail($id);

        try {
            $ictForm = IctAccessResource::findOrFail($id);
            $ictForm->hardware_request = explode(',', $ictForm->hardware_request);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('some.error.route')->with('error', 'Clearance form not found.');
        }

        return view('ict-access-form.edit', compact('ictForm', 'form', 'user', 'qualifications', 'privileges', 'hmis'));
    }

    public function updateIctForm(Request $request, string $id)
    {
        $validate = Validator::make($request->all(), [
            'privilegeId' => 'required|exists:privilege_levels,id',
            'hmisId' => 'required|exists:h_m_i_s_access_levels,id',
            'aruti' => 'required|exists:privilege_levels,id',
            'sap' => 'required|exists:privilege_levels,id',
            'nhifId' => 'required|exists:nhif_qualifications,id',
            'active_drt' => 'required|exists:privilege_levels,id',
            'VPN' => 'required|exists:privilege_levels,id',
            'pbax' => 'required|exists:privilege_levels,id',
        ]);
    }

    public function editClearance(string $id)
    {
        $user = Auth::user();
        $qualifications = NhifQualification::where('delete_status', 0)->get();
        $privileges = PrivilegeLevel::where('delete_status', 0)->get();
        $hmis = HMISAccessLevel::where('delete_status', 0)->get();

        try {
            $clearform = ClearanceForm::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('some.error.route')->with('error', 'Clearance form not found.');
        }

        return view('clearance.edit', compact('clearform', 'user', 'qualifications', 'privileges', 'hmis'));
    }

    public function update(Request $request, string $id)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'date' => 'required|date',
            'ccbrt_id_card' => 'required|string',
            'ccbrt_name_tag' => 'nullable|string',
            'nhif_cards' => 'nullable|string',
            'work_permit_cancelled' => 'required|boolean',
            'residence_permit_cancelled' => 'required|boolean',
            'repaid_salary_advance' => 'required|boolean',
            'loan_balances_informed' => 'required|boolean',
            'repaid_outstanding_imprest' => 'required|boolean',
            'changing_room_keys' => 'required|boolean',
            'office_keys' => 'required|boolean',
            'mobile_phone' => 'required|boolean',
            'camera' => 'required|boolean',
            'ccbrt_uniforms' => 'required|boolean',
            'office_car_keys' => 'required|boolean',
            'other_items' => 'nullable|string',
            'laptop_returned' => 'required|boolean',
            'access_card_returned' => 'required|boolean',
            'domain_account_disabled' => 'required|boolean',
            'email_account_disabled' => 'required|boolean',
            'telephone_pin_disabled' => 'required|boolean',
            'openclinic_account_disabled' => 'required|boolean',
            'sap_account_disabled' => 'required|boolean',
            'aruti_account_disabled' => 'required|boolean',
        ]);

        $clearform = ClearanceForm::findOrFail($id);
        $clearform->update($validatedData);

        return redirect()->route('clearance.index')->with('success', 'Clearance form updated successfully');
    }

    public function search(Request $request)
    {
        $query = $request->input('query');

        // Perform your query to filter requests based on the $query parameter
        $requests = Workflow::where('field_to_search', 'like', '%' . $query . '%')->get();

        return view('requests.index', compact('requests'));
    }
}
