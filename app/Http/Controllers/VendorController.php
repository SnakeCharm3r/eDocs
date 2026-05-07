<?php

namespace App\Http\Controllers;

use RealRashid\SweetAlert\Facades\Alert;
use App\Models\CcbrtVendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = CcbrtVendor::get();
        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        //take me to the vendors view for addition
        return view('vendors.create');
    }

    public function store(Request $request)
{
    try {
        // ✅ ADD THIS: Check for file upload errors before validation
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $document) {
                if (!$document->isValid()) {
                    return redirect()->back()->with('error', 
                        "File upload failed: " . $document->getErrorMessage() . 
                        " (File: " . $document->getClientOriginalName() . ")"
                    )->withInput();
                }
            }
        }

        // Validate input with Tanzania regulatory requirements
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,external,goods,services,goods_and_services',
            'owner_name' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'registration_number' => 'required|regex:/^\d+$/', 
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:ccbrt_vendors,contact_email',
            'contact_phone' => ['required','regex:/^[\d\+\-\(\)\s]{6,25}$/'],
            'address' => 'required|string|max:500',
            'tax_number' => 'required|string|max:100|unique:ccbrt_vendors,tax_number|regex:/^\d{9}$/',
            'rating' => 'nullable|integer|between:1,5',
            'status' => 'required|in:active,inactive',
            'currency' => 'nullable|string|max:10',
            'documents' => 'required|array|min:1',
            'documents.*' => 'required|file|mimes:pdf,docx|max:51200',
        ], [
            // Custom error messages for regulatory fields
            'tax_number.required' => 'TIN Number is required by Tanzania Revenue Authority (TRA)',
            'tax_number.unique' => 'This TIN Number is already registered in the system',
            'tax_number.regex' => 'TIN Number must be exactly 9 digits',
            'registration_number.required' => 'Business Registration Number (License) is required by BRELA',
            'registration_number.regex' => 'Business Registration Number must contain numbers only',
            'documents.required' => 'Contract document(s) are required for vendor registration',
            'documents.min' => 'At least one contract document must be uploaded',
            'documents.*.file' => 'File upload failed. Please check file size and try again.',
            'documents.*.uploaded' => 'File upload failed. The file may be too large or corrupted.',
        ]);

        // Handle file uploads for contract documents
        $attachments = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $path = $document->store('vendor-documents');
                
                $attachments[] = [
                    'original_name' => $document->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $document->getSize(),
                    'file_type' => $document->getClientOriginalExtension(),
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }
        }

        // Handle "other" industry selection
        $industry = $validated['industry'];
        if ($industry === 'other' && $request->has('other_industry_specify')) {
            $industry = $request->other_industry_specify;
        }

        // Create vendor with Tanzania regulatory data
        CcbrtVendor::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'owner_name' => $validated['owner_name'],
            'industry' => $industry,
            'registration_number' => $validated['registration_number'],
            'contact_person' => $validated['contact_person'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'address' => $validated['address'],
            'tax_number' => $validated['tax_number'],
            'rating' => $validated['rating'],
            'status' => $validated['status'],
            'currency' => $validated['currency'],
            'attachments' => $attachments,
            'attachments_count' => count($attachments),
            'registered_at' => now(),
        ]);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully!');

    } catch (\Illuminate\Validation\ValidationException $e) {
        // This will catch validation errors and redirect back with errors
        return redirect()->back()->withErrors($e->errors())->withInput();
        
    } catch (\Exception $e) {
        // This will catch any other errors (database, file system, etc.)
        \Log::error('Vendor creation failed: ' . $e->getMessage(), [
            'exception' => $e,
            'request_data' => $request->except('documents') // Don't log file contents
        ]);
        
        return redirect()->back()->with('error', 'Failed to create vendor. Please try again.')->withInput();
    }
}      

    //update the vendors Data
    public function update(Request $request, $id)
    {
        try {
            $vendor = CcbrtVendor::findOrFail($id);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'type' => 'nullable|in:internal,external,goods,services,goods_and_services',
                'contact_person' => 'nullable|string|max:255',
                'contact_phone' => ['nullable', 'regex:/^[\d\+\-\(\)\s]{6,25}$/'],
                'contact_email' => 'nullable|email|unique:ccbrt_vendors,contact_email,' . $id,
                'address' => 'nullable|string|max:500',
                'rating' => 'nullable|integer|between:1,5',
                'owner_name' => 'nullable|string|max:255',
                'registration_number' => 'nullable|regex:/^\d+$/', // ✅ DIGITS ONLY, NO LENGTH LIMIT
                'tax_number' => 'nullable|string|max:100|unique:ccbrt_vendors,tax_number,' . $id . '|regex:/^\d{9}$/',
                'industry' => 'nullable|string|max:255',
                'status' => 'nullable|in:active,inactive',
                'currency' => 'nullable|string|max:10',
                'documents' => 'nullable|array',
                'documents.*' => 'file|mimes:pdf,docx|max:51200',
            ], [
                'tax_number.regex' => 'TIN Number must be exactly 9 digits',
                'registration_number.regex' => 'Business Registration Number must contain numbers only',
            ]);

            // Handle "other" industry selection for update
            if (isset($validated['industry']) && $validated['industry'] === 'other' && $request->has('other_industry_specify')) {
                $validated['industry'] = $request->other_industry_specify;
            }

            // Get existing attachments
            $existingAttachments = $vendor->attachments ?? [];
            
            // Handle new file uploads
            if ($request->hasFile('documents')) {
                $newAttachments = [];
                
                foreach ($request->file('documents') as $document) {
                    $path = $document->store('vendor-documents');
                    
                    $newAttachments[] = [
                        'original_name' => $document->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $document->getSize(),
                        'file_type' => $document->getClientOriginalExtension(),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
                
                // Merge existing with new attachments
                $allAttachments = array_merge($existingAttachments, $newAttachments);
                
                // Add attachments data to validated array
                $validated['attachments'] = $allAttachments;
                $validated['attachments_count'] = count($allAttachments);
            } else {
                // Keep existing attachments if no new files
                $validated['attachments'] = $existingAttachments;
                $validated['attachments_count'] = count($existingAttachments);
            }
            
            // Handle removed files
            if ($request->has('removed_files')) {
                $removedIndexes = $request->removed_files;
                
                foreach ($removedIndexes as $index) {
                    if (isset($existingAttachments[$index])) {
                        // Delete file from storage
                        \Storage::delete($existingAttachments[$index]['file_path']);
                        // Remove from array
                        unset($existingAttachments[$index]);
                    }
                }
                
                // Reindex array
                $existingAttachments = array_values($existingAttachments);
                $validated['attachments'] = $existingAttachments;
                $validated['attachments_count'] = count($existingAttachments);
            }

            // Update vendor with all data including attachments
            $vendor->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Vendor updated successfully!',
                'vendor' => $vendor
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error("Vendor update failed: {$e->getMessage()}", [
                'id' => $id,
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    //Soft delete the Vendor
    public function destroy($id)
    {
        try {
            $vendor = CcbrtVendor::findOrFail($id);
            $vendor->update([
                'status' => 'inactive',
                'vendor_rating' => ''
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Vendor deletion failed: {$e->getMessage()}", [
                'id' => $id,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    //Download method for vendor attachments
    public function download($vendorId, $fileIndex)
{
    $vendor = CcbrtVendor::findOrFail($vendorId);
    
    if (!isset($vendor->attachments[$fileIndex])) {
        abort(404);
    }
    
    $attachment = $vendor->attachments[$fileIndex];
    $filePath = $attachment['file_path'];
    
    // Simple file download - one line!
    return \Storage::download($filePath, $attachment['original_name']);
}

}