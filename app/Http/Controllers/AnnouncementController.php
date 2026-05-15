<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\HrDocuments;
use App\Models\Departments;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Mail\AnnouncementNotification;


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
            'content' => 'nullable|string',
            'pdf' => 'nullable|file|mimes:pdf|max:5120', // 5MB
        ], [], ['content' => 'Content', 'pdf' => 'PDF']);

        $content = $request->input('content');
        $hasContent = $content && trim(strip_tags($content)) !== '';
        $hasPdf = $request->hasFile('pdf');
        if (!$hasContent && !$hasPdf) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['content' => 'Please provide either Content or upload a PDF (at least one is required).']);
        }

        $announcement = new Announcement();
        $announcement->title = $request->input('title');
        $announcement->content = $content ?? ''; // Set content (may be empty if PDF provided)
        $announcement->userId = auth()->id();

        if ($hasPdf) {
            $pdfPath = $request->file('pdf')->store('pdfs', 'public');
            $announcement->pdf_path = $pdfPath;
        }

        $announcement->save();

        // Notify staff (users with email) but NOT CEO
        $recipients = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'ceo');
            })
            ->get();
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->queue(new AnnouncementNotification($announcement, $recipient, true));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement created successfully.');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $canManage = $user->hasAnyRole(['hr', 'line-manager', 'cfo', 'cms', 'coo', 'super-admin', 'Super-Admin']);
        
        $query = Announcement::with('user')->latest();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $announcements = $query->get();
        $viewedAnnouncementIds = $this->viewedAnnouncementIds($announcements, $user);
        $unreadCount = $announcements->whereNotIn('id', $viewedAnnouncementIds)->count();
        $this->markAnnouncementsViewed($announcements, $user);

        return view('announcements.index', compact(
            'announcements',
            'canManage',
            'viewedAnnouncementIds',
            'unreadCount'
        ));
    }

    public function show($id)
    {
        $announcement = Announcement::findOrFail($id);
        return view('announcements.show', compact('announcement'));
    }

    /**
     * Record a view for an announcement (increment view_count). Called when user opens the View modal.
     */
    public function recordView($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->increment('view_count');
        if (Auth::check() && Schema::hasTable('announcement_user_views')) {
            DB::table('announcement_user_views')->updateOrInsert(
                [
                    'announcement_id' => $announcement->id,
                    'user_id' => Auth::id(),
                ],
                [
                    'viewed_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        $announcement->refresh();
        return response()->json(['view_count' => $announcement->view_count]);
    }

    private function markAnnouncementsViewed($announcements, User $user): void
    {
        if ($announcements->isEmpty() || !Schema::hasTable('announcement_user_views')) {
            return;
        }

        $now = now();
        $rows = $announcements->map(fn ($announcement) => [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'viewed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('announcement_user_views')->upsert(
            $rows,
            ['announcement_id', 'user_id'],
            ['viewed_at', 'updated_at']
        );
    }

    private function viewedAnnouncementIds($announcements, User $user): array
    {
        if ($announcements->isEmpty() || !Schema::hasTable('announcement_user_views')) {
            return [];
        }

        return DB::table('announcement_user_views')
            ->where('user_id', $user->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->pluck('announcement_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

public function edit($id)
{
    $user = Auth::user();
    $announcement = Announcement::findOrFail($id);

    // Only the creator or super-admin can edit
    if ($announcement->userId != $user->id && !$user->hasAnyRole(['super-admin', 'Super-Admin'])) {
        abort(403, 'Only the person who created this announcement can edit it.');
    }

    return view('announcements.edit', compact('announcement'));
}

public function update(Request $request, $id)
{
    $user = Auth::user();
    $announcement = Announcement::findOrFail($id);

    // Only the creator or super-admin can update
    if ($announcement->userId != $user->id && !$user->hasAnyRole(['super-admin', 'Super-Admin'])) {
        abort(403, 'Only the person who created this announcement can edit it.');
    }

    $request->validate([
        'title' => 'required|string|max:255',
        'content' => 'nullable|string',
        'pdf' => 'nullable|file|mimes:pdf|max:5120', // 5MB
    ], [], ['content' => 'Content', 'pdf' => 'PDF']);

    $content = $request->input('content');
    $hasContent = $content && trim(strip_tags($content)) !== '';
    $hasPdf = $request->hasFile('pdf');
    $existingPdf = Announcement::find($id)?->pdf_path ?? null;
    if (!$hasContent && !$hasPdf && !$existingPdf) {
        return redirect()->back()
            ->withInput()
            ->withErrors(['content' => 'Please provide either Content or upload a PDF (at least one is required).']);
    }

    try {
        $announcement = Announcement::findOrFail($id);

        $announcement->title = $request->input('title');
        $announcement->content = $content ?? $announcement->content ?? '';

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

        // Notify staff (users with email) but NOT CEO
        $recipients = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'ceo');
            })
            ->get();
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient->email)->queue(new AnnouncementNotification($announcement, $recipient, false));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement updated successfully.');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'An error occurred while updating the announcement. Please try again.');
    }
}


public function destroy($id)
{
    $user = Auth::user();
    $announcement = Announcement::findOrFail($id);

    // Only the creator or super-admin can delete
    if ($announcement->userId != $user->id && !$user->hasAnyRole(['super-admin', 'Super-Admin'])) {
        abort(403, 'Only the person who created this announcement can delete it.');
    }

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

public function view($DocId)
{
    // Find the document by ID
    $document = HrDocuments::findOrFail($DocId);

    // Get the full file path
    $filePath = storage_path('app/public/' . $document->DocumentPath);
    
    // Check if the file exists
    if (file_exists($filePath)) {
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->DocumentName . '.pdf"'
        ]);
    } else {
        return back()->withErrors(['error' => 'File not found!']);
    }
}

public function destroyhrdoc($id)
{
    // Check permission
    $user = Auth::user();
    if (!$user->hasAnyRole(['hr', 'super-admin', 'Admin', 'coo', 'cfo', 'cms', 'it'])) {
        abort(403, 'You do not have permission to delete HR documents.');
    }

    // Find the document by its ID
    $document = HrDocuments::findOrFail($id);

    // Delete the file from storage
    Storage::disk('public')->delete($document->DocumentPath);

    // Delete the document record from the database
    $document->delete();

    // Redirect back with success message
    return redirect()->route('HrDocuments.index')->with('success', 'Document deleted successfully.');
}

public function editHrDocument($id)
{
    // Check permission
    $user = Auth::user();
    if (!$user->hasAnyRole(['hr', 'super-admin', 'Admin', 'coo', 'cfo', 'cms', 'it'])) {
        abort(403, 'You do not have permission to edit HR documents.');
    }

    $document = HrDocuments::findOrFail($id);
    return response()->json([
        'success' => true,
        'document' => [
            'DocId' => $document->DocId,
            'DocumentName' => $document->DocumentName,
            'Type' => $document->Type,
        ]
    ]);
}

public function updateHrDocument(Request $request, $id)
{
    // Check permission
    $user = Auth::user();
    if (!$user->hasAnyRole(['hr', 'super-admin', 'Admin', 'coo', 'cfo', 'cms', 'it'])) {
        abort(403, 'You do not have permission to update HR documents.');
    }

    // Validate the incoming request data
    $validated = $request->validate([
        'DocumentName' => 'required|string|max:255',
        'Type' => 'required|string|max:255',
        'DocumentPath' => 'nullable|mimes:pdf|max:5024',  // Optional file update
    ]);

    // Find the document
    $document = HrDocuments::findOrFail($id);

    // Update document name and type
    $document->DocumentName = $validated['DocumentName'];
    $document->Type = $validated['Type'];

    // Handle file upload if a new file is provided
    if ($request->hasFile('DocumentPath')) {
        // Delete old file
        Storage::disk('public')->delete($document->DocumentPath);
        
        // Store the new file
        $pdfPath = $request->file('DocumentPath')->store('Hrdocuments', 'public');
        $document->DocumentPath = $pdfPath;
    }

    $document->save();

    return redirect()->route('HrDocuments.index')->with('success', 'Document updated successfully.');
}


}

