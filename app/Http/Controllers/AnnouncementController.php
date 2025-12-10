<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HrDocuments;
use App\Models\Departments;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;


class AnnouncementController extends Controller
{
    //

    public function create()
    {
        // Check if user has permission to create announcements
        $user = Auth::user();
        if (!$user->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin'])) {
            abort(403, 'You do not have permission to create announcements.');
        }
        
        return view('announcements.create');
    }

    public function store(Request $request)
    {
        // Check if user has permission to create announcements
        $user = Auth::user();
        if (!$user->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin'])) {
            abort(403, 'You do not have permission to create announcements.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string', 
            'pdf' => 'nullable|file|mimes:pdf|max:5120', // Increased to 5MB
        ]);

        $announcement = new Announcement();
        $announcement->title = $request->input('title');
        $announcement->content = $request->input('content'); // Set content
        $announcement->userId = auth()->id(); // Set the user ID from the authenticated user

        if ($request->hasFile('pdf')) {
            $pdfPath = $request->file('pdf')->store('pdfs', 'public');
            $announcement->pdf_path = $pdfPath;
        }

        $announcement->save();

        return redirect()->route('announcements.index')->with('success', 'Announcement created successfully.');
    }

    public function index()
    {
        $announcements = Announcement::with('user')->latest()->get();
        return view('announcements.index', compact('announcements'));
    }

    public function show($id)
{
    $announcement = Announcement::findOrFail($id);
    return view('announcements.show', compact('announcement'));
}

public function edit($id)
{
    // Check if user has permission to edit announcements
    $user = Auth::user();
    if (!$user->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin'])) {
        abort(403, 'You do not have permission to edit announcements.');
    }

    $announcement = Announcement::findOrFail($id);
    return view('announcements.edit', compact('announcement'));
}

public function update(Request $request, $id)
{
    // Check if user has permission to update announcements
    $user = Auth::user();
    if (!$user->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin'])) {
        abort(403, 'You do not have permission to update announcements.');
    }

    $request->validate([
        'title' => 'required|string|max:255', 
        'content' => 'required|string',
        'pdf' => 'nullable|file|mimes:pdf|max:5120', // Increased to 5MB
    ]);

    try {
        $announcement = Announcement::findOrFail($id);

        $announcement->title = $request->input('title');
        $announcement->content = $request->input('content');
        
        // If a new PDF is uploaded, replace the old one
        if ($request->hasFile('pdf')) {
            if ($announcement->pdf_path) {
                Storage::disk('public')->delete($announcement->pdf_path);
            }

            // Store the new PDF and save the path in the database
            $pdfPath = $request->file('pdf')->store('pdfs', 'public');
            $announcement->pdf_path = $pdfPath;
        }

        $announcement->save();

        return redirect()->route('announcements.index')->with('success', 'Announcement updated successfully.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'An error occurred while updating the announcement. Please try again.');
    }
}


public function destroy($id)
{
    // Check if user has permission to delete announcements
    $user = Auth::user();
    if (!$user->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin'])) {
        abort(403, 'You do not have permission to delete announcements.');
    }

    $announcement = Announcement::findOrFail($id);
    
    // Delete associated PDF if exists
    if ($announcement->pdf_path) {
        Storage::disk('public')->delete($announcement->pdf_path);
    }
    
    $announcement->delete();
    return redirect()->route('announcements.index')->with('success', 'Announcement deleted successfully.');
}


public function ViewHRDocuments()
{
    // Fetch all HR documents from the database
    $documents = HrDocuments::all();  // or use pagination if needed, e.g., HrDocuments::paginate(10)

    return view('HrDocuments.index', compact('documents'));
}


public function addview()
{
    return view('HrDocuments.create');
}

public function add(Request $request)
{
    // Validate the incoming request data
    $validated = $request->validate([
        'DocumentName' => 'required|string|max:255',
        'Type' => 'required|string|max:255',
        'DocumentPath' => 'required|mimes:pdf|max:5024',  // Only PDF files allowed, max size 5MB
    ]);

    // Handle the file upload
    if ($request->hasFile('DocumentPath')) {
        // Store the file in the 'Hrdocuments' directory within the 'public' disk
        $pdfPath = $request->file('DocumentPath')->store('Hrdocuments', 'public');
    } else {
        return back()->withErrors(['DocumentPath' => 'Please upload a valid PDF file.']);
    }

    // Create a new document record and save it to the database
    $document = new HrDocuments();
    $document->DocumentName = $validated['DocumentName'];  // Set Document Name
    $document->Type = $validated['Type'];  // Set Document Type
    $document->DocumentPath = $pdfPath;  // Store the file path in the DB
    $document->save();  // Save the new document record

    // Redirect to the documents list page with a success message
    return redirect()->route('HrDocuments.index')->with('success', 'Document uploaded successfully.');
}

//pul changes mpyaaaa
public function download($DocId)
    {
        
        // Find the document by ID
        $document = HrDocuments::findOrFail($DocId);

        // Get the full file path
        $filePath = storage_path('app/public/' . $document->DocumentPath);
        
        //log the full path for debugging
        \Log::info("File path:".$filePath);

        // Check if the file exists
        if (file_exists($filePath)) {
            return response()->download($filePath, $document->DocumentName .'.pdf',['Content-type=>application/pdf']);
            
        } else {
            return back()->withErrors(['error' => 'File not found!']);
        }
    }

public function destroyhrdoc($id)
{
    // Find the document by its ID
    $document = HrDocuments::findOrFail($id);

    // Delete the file from storage
    Storage::disk('public')->delete($document->DocumentPath);

    // Delete the document record from the database
    $document->delete();

    // Redirect back with success message
    return redirect()->route('HrDocuments.index')->with('success', 'Document deleted successfully.');
}


}

