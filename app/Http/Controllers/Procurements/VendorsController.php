<?php

namespace App\Http\Controllers\Procurements;

use App\Http\Controllers\Controller;
use App\Models\CcbrtVendor;
use App\Models\VendorScore;
use App\Models\CcbrtContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;

class VendorsController extends Controller
{
    /**
     * Display a listing of vendors
     */
    public function index()
    {
        $vendors = CcbrtVendor::with(['contracts', 'scores'])->withCount('contracts')->latest()->get();
        return view('procurements.vendors.index', compact('vendors'));
    }

    /**
     * Show the form for creating a new vendor
     */
    public function create()
    {
        return view('procurements.vendors.create');
    }

    /**
     * Store a newly created vendor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,external,goods,services,goods_and_services',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:ccbrt_vendors,contact_email',
            'contact_phone' => 'nullable|string|max:20',
            'alternative_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'owner_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'other_industry_specify' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'rating' => 'nullable|integer|min:1|max:5',
            'years_in_business' => 'nullable|integer|min:0',
            'number_of_employees' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx|max:51200', // 50MB max
            'document_names' => 'nullable|array',
            'document_names.*' => 'nullable|string|max:255',
        ]);

        // Handle "Other" industry - use the specified value if "other" is selected
        if ($request->industry === 'other' && $request->filled('other_industry_specify')) {
            $validated['industry'] = $request->other_industry_specify;
        }

        // Handle file uploads with document names
        $attachments = [];
        if ($request->hasFile('documents')) {
            $documentNames = $request->input('document_names', []);
            $files = $request->file('documents');
            
            foreach ($files as $index => $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('vendor_documents', $filename, 'public');
                
                $attachment = [
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => '/storage/' . $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                ];
                
                // Add document name if provided
                if (isset($documentNames[$index]) && !empty($documentNames[$index])) {
                    $attachment['document_name'] = $documentNames[$index];
                } else {
                    // Use original filename as fallback
                    $attachment['document_name'] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                }
                
                $attachments[] = $attachment;
            }
        }

        // Store attachments as JSON
        if (!empty($attachments)) {
            $validated['attachments'] = json_encode($attachments);
        }

        // Remove fields that are not in the database
        unset($validated['other_industry_specify']);

        CcbrtVendor::create($validated);

        return redirect()->route('procurements.vendors.index')
            ->with('success', 'Vendor created successfully!');
    }

    /**
     * Display the specified vendor
     */
    public function show($id)
    {
        $vendor = CcbrtVendor::with(['contracts', 'scores.scorer', 'scores.contract'])->findOrFail($id);
        return view('procurements.vendors.show', compact('vendor'));
    }

    /**
     * Show the form for editing the specified vendor
     */
    public function edit($id)
    {
        $vendor = CcbrtVendor::findOrFail($id);
        return view('procurements.vendors.edit', compact('vendor'));
    }

    /**
     * Update the specified vendor
     */
    public function update(Request $request, $id)
    {
        $vendor = CcbrtVendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,external,goods,services,goods_and_services',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:ccbrt_vendors,contact_email,' . $vendor->id,
            'contact_phone' => 'nullable|string|max:20',
            'alternative_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'owner_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'other_industry_specify' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
            'rating' => 'nullable|integer|min:1|max:5',
            'years_in_business' => 'nullable|integer|min:0',
            'number_of_employees' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string|max:1000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,doc,docx|max:51200', // 50MB max
            'document_names' => 'nullable|array',
            'document_names.*' => 'nullable|string|max:255',
        ]);

        // Handle "Other" industry - use the specified value if "other" is selected
        if ($request->industry === 'other' && $request->filled('other_industry_specify')) {
            $validated['industry'] = $request->other_industry_specify;
        }

        // Handle new file uploads with document names
        $existingAttachments = json_decode($vendor->attachments ?? '[]', true) ?: [];
        if ($request->hasFile('documents')) {
            $documentNames = $request->input('document_names', []);
            $files = $request->file('documents');
            
            foreach ($files as $index => $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('vendor_documents', $filename, 'public');
                
                $attachment = [
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => '/storage/' . $path,
                    'file_type' => $file->getClientOriginalExtension(),
                    'file_size' => $file->getSize(),
                ];
                
                // Add document name if provided
                if (isset($documentNames[$index]) && !empty($documentNames[$index])) {
                    $attachment['document_name'] = $documentNames[$index];
                } else {
                    // Use original filename as fallback
                    $attachment['document_name'] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                }
                
                $existingAttachments[] = $attachment;
            }
        }

        if (!empty($existingAttachments)) {
            $validated['attachments'] = json_encode($existingAttachments);
        }

        $vendor->update($validated);

        // Handle AJAX requests
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully!'
            ]);
        }

        return redirect()->route('procurements.vendors.index')
            ->with('success', 'Vendor updated successfully!');
    }

    /**
     * Remove the specified vendor
     */
    public function destroy($id)
    {
        $vendor = CcbrtVendor::withCount('contracts')->findOrFail($id);

        // Check if vendor has any contracts
        if ($vendor->contracts_count > 0) {
            return redirect()->route('procurements.vendors.index')
                ->with('error', 'Cannot delete vendor. This vendor has ' . $vendor->contracts_count . ' contract(s) associated. Please remove or reassign the contracts first.');
        }

        // Delete associated files
        if ($vendor->attachments) {
            $attachments = json_decode($vendor->attachments, true);
            if (is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['file_path'])) {
                        $filePath = str_replace('/storage/', '', $attachment['file_path']);
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }
        }

        $vendor->delete();

        return redirect()->route('procurements.vendors.index')
            ->with('success', 'Vendor deleted successfully!');
    }

    /**
     * Rate a vendor
     */
    public function rate(Request $request, $id)
    {
        $vendor = CcbrtVendor::findOrFail($id);

        $validated = $request->validate([
            'score_value' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
            'contract_id' => 'nullable|exists:ccbrt_contracts,id',
            'rating_type' => 'nullable|string|in:overall,contract_performance,quality,delivery,communication',
        ]);

        $vendorScore = VendorScore::create([
            'vendor_id' => $vendor->id,
            'contract_id' => $validated['contract_id'] ?? null,
            'scored_by' => Auth::id(),
            'score_value' => $validated['score_value'],
            'comments' => $validated['comments'] ?? null,
            'rating_type' => $validated['rating_type'] ?? 'overall',
        ]);

        // Update vendor's overall rating if it's an overall rating
        if (($validated['rating_type'] ?? 'overall') === 'overall') {
            $vendor->rating = $vendor->average_rating;
            $vendor->save();
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor rated successfully!',
                'average_rating' => $vendor->average_rating,
            ]);
        }

        return redirect()->back()->with('success', 'Vendor rated successfully!');
    }

    /**
     * Get vendor ratings
     */
    public function ratings($id)
    {
        $vendor = CcbrtVendor::with(['scores.scorer', 'scores.contract'])->findOrFail($id);
        return response()->json([
            'scores' => $vendor->scores,
            'average_rating' => $vendor->average_rating,
            'total_ratings' => $vendor->scores->count(),
        ]);
    }

    /**
     * Display vendors related to contracts in the line manager's department
     */
    public function departmentVendors()
    {
        $user = Auth::user();

        // Check if user is a line manager
        if (!$user->hasRole('line-manager')) {
            return redirect()->route('procurements.vendors.index')
                ->with('error', 'Access denied. This page is only available for Line Managers.');
        }

        // Get line manager's department ID
        $departmentId = $user->deptId;

        if (!$departmentId) {
            return redirect()->route('procurements.vendors.index')
                ->with('error', 'You are not assigned to any department. Please contact the administrator.');
        }

        // Get all vendor IDs that have contracts in this department
        $vendorIds = CcbrtContract::where('department_id', $departmentId)
            ->whereNotNull('vendor_id')
            ->distinct()
            ->pluck('vendor_id')
            ->toArray();

        // Get vendors with their contracts and scores
        $vendors = CcbrtVendor::whereIn('id', $vendorIds)
            ->withCount(['contracts' => function($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            }])
            ->with(['contracts' => function($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->select('id', 'vendor_id', 'contract_number', 'title', 'status', 'start_date', 'end_date')
                    ->orderBy('created_at', 'desc');
            }])
            ->with(['scores' => function($query) {
                $query->where('scored_by', Auth::id())
                    ->latest()
                    ->limit(1);
            }])
            ->latest()
            ->get();

        // Get department name
        $department = \App\Models\Departments::find($departmentId);

        return view('procurements.vendors.department-vendors', compact('vendors', 'department'));
    }

    /**
     * Display vendors related to contracts in HEC member's departments
     */
    public function hecDepartmentVendors(Request $request)
    {
        $user = Auth::user();

        // Check if user is a HEC member (COO, CFO, CMS, CRHDO)
        $hecRoles = ['coo', 'cfo', 'cms', 'crhdo'];
        $isHecMember = false;
        $userHecRole = null;
        foreach ($hecRoles as $role) {
            if ($user->hasRole($role)) {
                $isHecMember = true;
                $userHecRole = $role;
                break;
            }
        }

        if (!$isHecMember) {
            return redirect()->route('procurements.vendors.index')
                ->with('error', 'Access denied. This page is only available for HEC members.');
        }

        // Get filter parameters
        $filterDepartment = $request->input('department');
        $filterVendorType = $request->input('vendor_type');
        $filterVendorName = $request->input('vendor_name');
        $filterIndustry = $request->input('industry');
        $filterStatus = $request->input('status');

        // COO can view all vendors (no department filter)
        $departmentIds = [];
        $allDepartments = collect();
        if ($user->hasRole('coo')) {
            // Get all departments for COO
            $allDepartments = \App\Models\Departments::all();
            
            // If department filter is applied, use it; otherwise get all vendor IDs
            if ($filterDepartment) {
                $departmentIds = [(int)$filterDepartment];
                $vendorIds = CcbrtContract::whereIn('department_id', $departmentIds)
                    ->whereNotNull('vendor_id')
                    ->distinct()
                    ->pluck('vendor_id')
                    ->toArray();
            } else {
                // Get all vendor IDs from all contracts (no department filter)
                $vendorIds = CcbrtContract::whereNotNull('vendor_id')
                    ->distinct()
                    ->pluck('vendor_id')
                    ->toArray();
            }
        } else {
            // Map HEC role to HEC level name (same logic as ContractsController)
            $roleToHec = [];
            if ($user->hasRole('cfo')) $roleToHec[] = 'CFO';
            if ($user->hasRole('cms')) $roleToHec[] = 'CMS';
            if ($user->hasRole('crhdo')) $roleToHec[] = 'CRHDO';

            // Get all department IDs mapped to this HEC member's HEC level
            // This matches the logic used in ContractsController::allowedDepartmentIds()
            $baseDepartmentIds = \App\Models\Departments::query()
                ->join('hecs', 'departments.hec_id', '=', 'hecs.id')
                ->whereIn(DB::raw('UPPER(TRIM(hecs.hec_level_name))'), collect($roleToHec)->map(fn($r) => strtoupper(trim($r)))->all())
                ->pluck('departments.id')
                ->toArray();

            if (empty($baseDepartmentIds)) {
                return redirect()->route('procurements.vendors.index')
                    ->with('error', 'No departments are mapped to your HEC level. Please contact the administrator.');
            }

            // If department filter is applied, use it if it's in the allowed departments
            if ($filterDepartment && in_array((int)$filterDepartment, $baseDepartmentIds)) {
                $departmentIds = [(int)$filterDepartment];
            } else {
                $departmentIds = $baseDepartmentIds;
            }

            // Get all vendor IDs that have contracts in these departments
            $vendorIds = CcbrtContract::whereIn('department_id', $departmentIds)
                ->whereNotNull('vendor_id')
                ->distinct()
                ->pluck('vendor_id')
                ->toArray();
            
            $allDepartments = \App\Models\Departments::whereIn('id', $baseDepartmentIds)->get();
        }

        // Get unique vendor types and industries for filter dropdowns (before filtering)
        $vendorTypes = collect([]);
        $industries = collect([]);
        if (!empty($vendorIds)) {
            $vendorTypes = CcbrtVendor::whereIn('id', $vendorIds)->distinct()->pluck('type')->filter()->sort()->values();
            $industries = CcbrtVendor::whereIn('id', $vendorIds)->distinct()->pluck('industry')->filter()->sort()->values();
        }

        // Use allDepartments for the view (for COO it's all departments, for others it's their assigned departments)
        $departments = $allDepartments;

        if (empty($vendorIds)) {
            return view('procurements.vendors.hec-department-vendors', [
                'vendors' => collect([]),
                'departments' => $departments,
                'allDepartments' => $allDepartments,
                'user' => $user,
                'filterDepartment' => $filterDepartment,
                'filterVendorType' => $filterVendorType,
                'filterVendorName' => $filterVendorName,
                'filterIndustry' => $filterIndustry,
                'filterStatus' => $filterStatus,
                'vendorTypes' => $vendorTypes,
                'industries' => $industries
            ]);
        }

        // Build vendor query with filters
        $vendorsQuery = CcbrtVendor::whereIn('id', $vendorIds);

        // Apply filters
        if ($filterVendorType) {
            $vendorsQuery->where('type', $filterVendorType);
        }

        if ($filterVendorName) {
            $vendorsQuery->where('name', 'like', '%' . $filterVendorName . '%');
        }

        if ($filterIndustry) {
            $vendorsQuery->where('industry', $filterIndustry);
        }

        if ($filterStatus) {
            $vendorsQuery->where('status', $filterStatus);
        }

        // Get vendors with their contracts and scores
        // For COO, show all contracts if no department filter; for others, filter by departmentIds
        $vendors = $vendorsQuery
            ->withCount(['contracts' => function($query) use ($departmentIds, $user, $filterDepartment) {
                if ($user->hasRole('coo')) {
                    if ($filterDepartment) {
                        $query->where('department_id', $filterDepartment);
                    }
                    // For COO without department filter, count all contracts
                } elseif (!empty($departmentIds)) {
                    $query->whereIn('department_id', $departmentIds);
                }
            }])
            ->with(['contracts' => function($query) use ($departmentIds, $user, $filterDepartment) {
                if ($user->hasRole('coo')) {
                    if ($filterDepartment) {
                        $query->where('department_id', $filterDepartment);
                    }
                    // For COO without department filter, show all contracts
                } elseif (!empty($departmentIds)) {
                    $query->whereIn('department_id', $departmentIds);
                }
                $query->select('id', 'vendor_id', 'contract_number', 'title', 'status', 'start_date', 'end_date', 'department_id')
                    ->orderBy('created_at', 'desc');
            }])
            ->with(['scores' => function($query) {
                $query->where('scored_by', Auth::id())
                    ->latest()
                    ->limit(1);
            }])
            ->latest()
            ->get();

        return view('procurements.vendors.hec-department-vendors', compact(
            'vendors', 
            'departments',
            'allDepartments',
            'user',
            'filterDepartment',
            'filterVendorType',
            'filterVendorName',
            'filterIndustry',
            'filterStatus',
            'vendorTypes',
            'industries'
        ));
    }
}

