<?php

namespace App\Http\Controllers;

use App\Models\AccessKeyCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AccessKeyCardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $keyCards = AccessKeyCard::where('delete_status', 0)
            ->with(['user', 'assignedBy'])
            ->orderBy('card_number', 'asc')
            ->get();
        return view('access-key-card.index', compact('keyCards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('access-key-card.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|string|max:255|unique:access_key_cards,card_number',
            'status' => 'required|in:active,not_active',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $keyCard = AccessKeyCard::create([
            'card_number' => $request->input('card_number'),
            'status' => $request->input('status'),
            'notes' => $request->input('notes'),
            'assigned_by' => Auth::id(),
            'delete_status' => 0,
        ]);

        return redirect()->route('access-key-card.index')->with('success', 'Access Key Card added successfully.');
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
        $keyCard = AccessKeyCard::findOrFail($id);
        return view('access-key-card.edit', compact('keyCard'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|string|max:255|unique:access_key_cards,card_number,' . $id,
            'status' => 'required|in:active,not_active',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Validation failed. Please check your input.');
        }

        $keyCard = AccessKeyCard::findOrFail($id);

        $keyCard->update([
            'card_number' => $request->input('card_number'),
            'status' => $request->input('status'),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('access-key-card.index')->with('success', 'Access Key Card updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $keyCard = AccessKeyCard::findOrFail($id);

        // Check if Access Key Card is being used in IctAccessResource
        $usageCount = \App\Models\IctAccessResource::where('access_key_card_id', $id)
            ->where('delete_status', '!=', '1')
            ->count();

        // Prevent deletion if Access Key Card is in use
        if ($usageCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete Access Key Card. It is currently being used by ' . $usageCount . ' ICT access resource(s). Please remove all references first before deleting.'
            ], 400);
        }

        // Soft delete by setting delete_status to 1
        $keyCard->update([
            'delete_status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Access Key Card deleted successfully!'
        ]);
    }
}
