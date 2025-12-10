<?php

namespace App\Http\Controllers;

use App\Models\PrivilegeLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;
class PrivilegeLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $priv = PrivilegeLevel::where('delete_status', 0)
            ->orderBy('prv_name', 'asc')
            ->get();
        return view('privilege-level.index', compact('priv'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       return view( 'privilege-level.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'prv_name' => 'required|string|max:255',
            'prv_status' => 'required|in:active,not_active',
            'can_use_for_domain_access' => 'nullable|boolean',
            'can_use_for_email_access' => 'nullable|boolean',
            'can_use_for_vpn_access' => 'nullable|boolean',
            'can_use_for_pbax_access' => 'nullable|boolean',
        ]);

        if($validator->fails()){
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        // Check if Privilege Level with same name already exists
        $existingPriv = PrivilegeLevel::where('prv_name', $request->prv_name)
            ->where('delete_status', '!=', '1')
            ->first();
            
        if($existingPriv){
            return redirect()->back()
                ->withInput()
                ->with('error', 'Privilege Level with this name already exists.');
        }

        $priv = PrivilegeLevel::create([
            'prv_name' => $request->input('prv_name'),
            'prv_status' => $request->input('prv_status'),
            'can_use_for_domain_access' => $request->has('can_use_for_domain_access') ? 1 : 0,
            'can_use_for_email_access' => $request->has('can_use_for_email_access') ? 1 : 0,
            'can_use_for_vpn_access' => $request->has('can_use_for_vpn_access') ? 1 : 0,
            'can_use_for_pbax_access' => $request->has('can_use_for_pbax_access') ? 1 : 0,
            'delete_status' => 0,
        ]);
        
        return redirect()->route('privilege.index')->with('success', 'Privilege Level added successfully.');
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
        $priv = PrivilegeLevel::findOrFail($id);
        return view('privilege-level.edit', compact('priv'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'prv_name' => 'required|string|max:255',
            'prv_status' => 'required|in:active,not_active',
            'can_use_for_domain_access' => 'nullable|boolean',
            'can_use_for_email_access' => 'nullable|boolean',
            'can_use_for_vpn_access' => 'nullable|boolean',
            'can_use_for_pbax_access' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $priv = PrivilegeLevel::findOrFail($id);
        
        // Check if another Privilege Level with same name already exists (excluding current one)
        $existingPriv = PrivilegeLevel::where('prv_name', $request->prv_name)
            ->where('id', '!=', $id)
            ->where('delete_status', '!=', '1')
            ->first();
            
        if($existingPriv){
            return redirect()->back()
                ->withInput()
                ->with('error', 'Privilege Level with this name already exists.');
        }
        
        $priv->update([
            'prv_name' => $request->input('prv_name'),
            'prv_status' => $request->input('prv_status'),
            'can_use_for_domain_access' => $request->has('can_use_for_domain_access') ? 1 : 0,
            'can_use_for_email_access' => $request->has('can_use_for_email_access') ? 1 : 0,
            'can_use_for_vpn_access' => $request->has('can_use_for_vpn_access') ? 1 : 0,
            'can_use_for_pbax_access' => $request->has('can_use_for_pbax_access') ? 1 : 0,
        ]);

        return redirect()->route('privilege.index')->with('success', 'Privilege Level updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $priv = PrivilegeLevel::findOrFail($id);
        
        // Check if Privilege Level is being used in IctAccessResource
        // PrivilegeLevel is used in multiple fields: privilegeId, aruti, VPN, pbax, active_drt, folder_privilege, email
        // Note: aruti, VPN, pbax, active_drt, email are stored as strings, so we need to cast
        $usageCount = \App\Models\IctAccessResource::where('delete_status', '!=', '1')
            ->where(function($query) use ($id) {
                $query->where('privilegeId', $id)
                    ->orWhere('folder_privilege', $id)
                    ->orWhere('aruti', (string)$id)
                    ->orWhere('VPN', (string)$id)
                    ->orWhere('pbax', (string)$id)
                    ->orWhere('active_drt', (string)$id)
                    ->orWhere('email', (string)$id);
            })
            ->count();
        
        // Prevent deletion if Privilege Level is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete Privilege Level. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }
    
        // Soft delete by setting delete_status to 1
        $priv->update([
            'delete_status' => 1
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Privilege Level deleted successfully!'
        ]);
    }
}
