<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFamilyDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class UserFamilyDetailsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $familyData = UserFamilyDetails::where('userId', $user->id)->get();
        return view('family-details.index', compact('familyData', 'user'));
    }

public function addFamilyData(Request $request)
{
    $user = Auth::user();
    $existingCount = UserFamilyDetails::where('userId', $user->id)->count();
    $remainingCount = 5 - $existingCount;

    if ($existingCount >= 5) {
        return redirect()->back()->with('error', 'You cannot add more than 5 family details.');
    }

    $request->validate([
        'familyData' => 'required|array|max:' . $remainingCount,
    ]);

    Log::info('Received request', $request->all());

    foreach ($request->familyData as $data) {
        if ($existingCount < 5) {
            $familyDetail = new UserFamilyDetails();
            $familyDetail->user_id = $user->id;
            $familyDetail->full_name = $data['full_name'];
            $familyDetail->relationship = $data['relationship'];
            $familyDetail->phone_number = $data['phone_number'];
            $familyDetail->occupation = $data['occupation'];
            $familyDetail->next_of_kin = isset($data['next_of_kin']) ? (bool) $data['next_of_kin'] : false;
            $familyDetail->save();
            $existingCount++;
        }
    }

    $remainingCount = 5 - $existingCount;
    if ($remainingCount > 0) {
        Alert::success('Successful', "Family details added successfully. You can add $remainingCount more.");
    } else {
        Alert::success('Successful', 'Family details added successfully. You have reached the limit of 5.');
    }

    return redirect()->route('family-details.index')->with('success', 'Family details added successfully.');
}



    public function edit($id)
    {
        $familyDetail = UserFamilyDetails::findOrFail($id);
        return view('family-details.edit',compact('familyDetail'));
    }

    // Update family member's details
    public function editData(Request $request, $id)
    {

        $request->validate([
            'full_name' => 'required|string',
            'relationship' => 'required|string',
            'DOB' => 'required|date',
            'phone_number' => 'nullable|string',
            'occupation' => 'nullable|string',
            'next_of_kin' => 'nullable|boolean',
        ]);
        // dd(1234);
        $familyDetail = UserFamilyDetails::findOrFail($id);
        $familyDetail->full_name = $request->input('full_name');
        $familyDetail->relationship = $request->input('relationship');
        $familyDetail->DOB = $request->input('DOB');
        $familyDetail->phone_number = $request->input('phone_number');
        $familyDetail->occupation = $request->input('occupation');
        $familyDetail->next_of_kin = (bool) $request->input('next_of_kin');
        $familyDetail->save();
        Alert::success('Successful', 'Family details edited successfully');
        return redirect()->route('family-details.index')->with('success', 'Family details updated successfully.');
    }


    // public function destroy($id)
    // {
    //     $familyDetail = UserFamilyDetails::findOrFail($id);
    //     $familyDetail->delete();

    //     return redirect()->back()->with('success', 'Family detail deleted successfully.');
    // }
    public function deleteFamilyData(string $id){
        $familyDetail = UserFamilyDetails::find($id);

        if(!$familyDetail){
            return response()->json([
                'status' => 400,
                'massage' => ' Family detail not found',
            ]);
        }

        // $familyDetail->update([
        //     'delete_status' => 1
        //  ]);
        $familyDetail->delete();

         return redirect()->back()->with('success', 'Family detail deleted successfully.');

        }




}
