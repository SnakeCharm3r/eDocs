<?php

namespace App\Http\Controllers;

use App\Models\CcbrtRelation;
use App\Models\HealthDetails;
use App\Models\LanguageKnowledge;
use App\Models\Policy;
use App\Models\User;
use App\Models\UserAdditionalInfo;
use App\Models\UserFamilyDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('department', 'jobTitle', 'employmentType');
        $policies = Policy::all();
        $nextOfKins = UserAdditionalInfo::where('userId', $user->id)->get();
        $familyData = UserFamilyDetails::where('userId', $user->id)->get();
        $healthDetails = HealthDetails::where('userId', $user->id)->get();
        $languageData = LanguageKnowledge::where('userId', $user->id)->get();
        $ccbrtRelation = CcbrtRelation::where('userId', $user->id)->get();
        $departments = \App\Models\Departments::all()->keyBy('id');
        $ccbrtRelation = $ccbrtRelation->map(function ($relation) use ($departments) {
            if ($relation->department) {
                $relation->department_name = $departments->get($relation->department)->dept_name ?? 'N/A';
            } else {
                $relation->department_name = 'N/A';
            }
            return $relation;
        });
        
        // Check license status for clinical users
        $licenseInfo = null;
        $isClinicalDepartment = $user->jobTitle && $user->jobTitle->clinical_or_non_clinical === 'Clinical';
        
        if ($isClinicalDepartment) {
            // Get HR workflow for this user
            $hrWorkflow = \App\Models\Workflow::where('hr_form', $user->id)->orderBy('id', 'desc')->first();
            
            if ($hrWorkflow) {
                // Get the most recent HR workflow history with license info
                $hrWorkflowHistory = \App\Models\WorkFlowHistory::where('work_flow_id', $hrWorkflow->id)
                    ->whereNotNull('license_valid_until')
                    ->orderBy('id', 'desc')
                    ->first();
                
                if ($hrWorkflowHistory) {
                    $expiryDate = \Carbon\Carbon::parse($hrWorkflowHistory->license_valid_until)->startOfDay();
                    $today = \Carbon\Carbon::now()->startOfDay();
                    $daysUntilExpiry = $expiryDate->diffInDays($today, false);
                    
                    $licenseInfo = [
                        'license_valid_until' => $hrWorkflowHistory->license_valid_until,
                        'license_provider' => $hrWorkflowHistory->license_provider,
                        'professional_reg_verified' => $hrWorkflowHistory->professional_reg_verified ?? false,
                        'professional_reg_verification_notes' => $hrWorkflowHistory->professional_reg_verification_notes,
                        'is_expired' => $expiryDate->lt($today),
                        'days_until_expiry' => $daysUntilExpiry,
                        'expiring_soon' => !$expiryDate->lt($today) && $daysUntilExpiry <= 0 && $daysUntilExpiry >= -20,
                    ];
                }
            }
        }
        
        return view('user_info.profile', compact('user', 'policies', 'nextOfKins', 'familyData', 'healthDetails', 'languageData', 'ccbrtRelation', 'licenseInfo', 'isClinicalDepartment'));
    }

    public function updateProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = Auth::user();

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $filePath = 'profile_pictures/' . $filename;

            Storage::disk('public')->put($filePath, file_get_contents($file));

            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $user->profile_picture = $filePath;
            $user->save();
        }
        Alert::success('successfully', 'Profile picture updated');
        return back()->with('success', 'Profile picture updated successfully.');
    }

    public function create()
    {
        //
    }

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
        $user = Auth::user();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = Auth::user()->load('department', 'jobTitle', 'employmentType');
        return view('user_profile.edit', compact('user'));
    }

    public function update(Request $request, string $id)
    {
        // Validate form data (without employee_cv)
        $request->validate([
            'mobile' => 'nullable|string|max:15',
            'house_no' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:20',
            'region' => 'required|string|max:20',
            'district' => 'required|string|max:20',
            'street' => 'required|string|max:20',
            'box_no' => 'nullable|integer|min:0|max:999999',
            'plot_no' => 'nullable|integer|min:0|max:999999',
            'popular_landmark' => 'required|string|max:50',
            // 'ccbrt_code' => 'required|string|unique:users,ccbrt_code,' . Auth::id(),
        ]);

        // Find user
        $user = User::findOrFail($id);

        // Update user fields
        $user->update($request->all());

        // Redirect with success message
        return redirect()->route('profile.index')->with('success', 'User updated successfully.');
    }


    public function destroy(Request $request)
    {
        // Get the currently authenticated user
        $user = Auth::user();

        // Check if the user has a profile picture
        if ($user->profile_picture) {
            // Delete the existing picture from storage
            Storage::delete('public/' . $user->profile_picture);

            // Remove the profile picture path from the user's record
            $user->profile_picture = null;
            $user->save();
        }

        // Redirect back with a success message
        return back()->with('success', 'Profile picture deleted successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();

        // Check if the old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            return redirect()->back()->withErrors(['old_password' => 'The current password is incorrect.'])->withInput();
        }

        // Update the password
        $user->password = Hash::make($request->password);
        $user->save();

        Alert::success('Success', 'Password changed successfully.');
        return redirect()->route('profile.index')->with('success', 'Password changed successfully.');
    }
}
