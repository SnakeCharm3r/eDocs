<?php

namespace App\Http\Controllers;

use App\Models\CcbrtRelation;
use App\Models\HealthDetails;
use App\Models\LanguageKnowledge;
use App\Models\UserAdditionalInfo;
use App\Models\UserFamilyDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserAdditionalInfoController extends Controller
{
    public function profile(){
        $user = Auth::user();
        $nextOfKins = UserAdditionalInfo::where('userId', $user->id)->get();
        $familyData =  UserFamilyDetails::where('userId', $user->id)->get();
        $healthDetails = HealthDetails::where('userId', $user->id)->get();
        $languageData = LanguageKnowledge::where('userId', $user->id)->get();
        $ccbrtRelation = CcbrtRelation::where('userId', $user->id)->get();
        return view('user_info.profile', compact('user',
        'nextOfKins', 'familyData', 'healthDetails','languageData','ccbrtRelation'));
    }

    public function addNextOfKins(Request $request){
        $validator = Validator::make($request->all(), [
            'full_name' => 'required',
            'relationship' => 'required',
            'mobile' => 'required',
            'userId' => 'required|exists:users,id',
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'error' => $validator->errors()
            ]);
        }

        $kins = UserAdditionalInfo::create([
            'full_name' => $request->input('full_name'),
            'relationship' => $request->input('relationship'),
            'address' => $request->input('address'),
            'mobile' => $request->input('mobile'),
            'email' => $request->input('email'),
            'occupation' => $request->input('occupation'),
            'userId' => $request->input('userId')
        ]);
        return redirect()->route('profile')->with('success', 'Next of Kin added successfully.');

    }

    public function addFamilyData(Request $request){
        $validator = Validator::make($request->all(), [
            'userId' => 'required|exists:users,id',
        'familyData' => 'required|array|min:2|max:5',
        'familyData.*.full_name' => 'required|string|max:255',
        'familyData.*.relationship' => 'required|string|max:255',
        ]);
        if($validator->fails()){
            return response()->json([
                'status' => 400,
                'error' => $validator->errors()
            ]);
        }

         $userId = $request->input('userId');
        $familyData = $request->input('familyData');

        foreach ($familyData as $data) {
            UserFamilyDetails::create([
                'userId' => $userId,
                'full_name' => $data['full_name'],
                'relationship' => $data['relationship'],
                'mobile' => $data['mobile'],
                'address' => $data['address'],
                'email' => $data['email'],
                'occupation' => $data['occupation'],
            ]);
        }
        return redirect()->route('profile')->with('success', 'Family Data added successfully.');

    }


 }
