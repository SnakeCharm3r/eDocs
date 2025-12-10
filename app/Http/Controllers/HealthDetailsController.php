<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HealthDetails;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
class HealthDetailsController extends Controller
{
    /**
     * Display a listing of the health details.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $healthDetails = HealthDetails::where('userId', $user->id)->get();

        return view('health-details.index', compact('healthDetails', 'user'));
    }

    /**
     * Store a newly created health detail in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function addHealthData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
            'physical_disability' => 'required',
            'health_insurance' => 'required',
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        // Check if the user already has a health detail
        $existingHealthDetail = HealthDetails::where('userId', $request->input('userId'))->first();
    
        if ($existingHealthDetail) {
            return redirect()->back()->with('error', 'You can only add one health detail.')->withInput();
        }
    
        HealthDetails::create([
            'userId' => $request->input('userId'),
            'physical_disability' => $request->input('physical_disability'),
            'blood_group' => $request->input('blood_group'),
            'illness_history' => $request->input('illness_history'),
            'health_insurance' => $request->input('health_insurance'),
            'insur_name' => $request->input('insur_name'),
            'insur_no' => $request->input('insur_no'),
            'allergies' => $request->input('allergies'),
            'delete_status' => 0,
        ]);
    
        Alert::success('Successful', 'Health Details added successfully');
        return redirect()->route('health-details.index')->with('success', 'Health details added successfully.');
    }
    

    public function edit($id)
    {
        $healthDetail = HealthDetails::find($id);
        return response()->json($healthDetail);
    }
    
    public function update(Request $request, $id)
    {
        $healthDetail = HealthDetails::findOrFail($id);

        $request->validate([
            'physical_disability' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:3',
            'illness_history' => 'nullable|string',
            'health_insurance' => 'nullable|string',
            'insur_name' => 'nullable|string|max:255',
            'insur_no' => 'nullable|string|max:255',
            'allergies' => 'nullable|string',
        ]);

        $healthDetail->update($request->all());
        Alert::success('Successful', 'Health Details updated successfully');
        return redirect()->route('health-details.index');
    }

    

 public function deleteHealthData(string $id){
    $health = HealthDetails::findOrFail($id);
    $health->delete();

   
    if(!$health){
        return response()->json([
            'status' => 400,
            'massage' => ' Health detail not found',
        ]);
    }

    $health->update([
        'delete_status' => 1 
     ]);
     Alert::success('Deleted', 'Health detail deleted successfully.');

     return redirect()->route('health-details.index');
    }


}
