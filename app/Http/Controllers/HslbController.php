<?php

namespace App\Http\Controllers;

use App\Models\Hslb;
use Illuminate\Http\Request;

class HslbController extends Controller
{
    public function index()
    {
        $declarations = Hslb::all();
        return view('hslb.index', compact('declarations'));
    }

    public function create()
    {
        return view('hslb.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'loan_status' => 'required',
            'form_iv_index_no' => 'required_if:loan_status,has_loan',
            'signature' => 'required',
            'date' => 'required|date',
        ]);

        Hslb::create([
            'user_id' => auth()->id(),
            'loan_status' => $request->loan_status,
            'form_iv_index_no' => $request->form_iv_index_no,
            'signature' => $request->signature,
            'date' => $request->date,
        ]);

        return redirect()->route('hslb.index')->with('success', 'Declaration submitted successfully.');
    }

    public function show($id)
    {
        $declaration = Hslb::findOrFail($id);
        return view('hslb.show', compact('declaration'));
    }

    public function edit($id)
    {
        $declaration = Hslb::findOrFail($id);
        return view('hslb.edit', compact('declaration'));
    }

    public function update(Request $request, $id)
    {
        $hslb = Hslb::findOrFail($id);

        $validatedData = $request->validate([
            'hr_comment' => 'nullable|string',
            'hr_member' => 'required|string',
            'hr_signature' => 'required|string',
            'hr_date' => 'required|date',
        ]);

        $hslb->update($validatedData);

        return redirect()->route('hslb.show', $hslb->id)->with('success', 'HSLB record updated successfully');
    }

    public function destroy($id)
    {
        $declaration = Hslb::findOrFail($id);
        $declaration->delete();

        return redirect()->route('hslb.index')->with('success', 'Declaration deleted successfully.');
    }

    public function hrConfirm(Request $request, $id)
    {
        $request->validate([
            'hr_comment' => 'required|string',
            'hr_signature' => 'required|string',
            'hr_date' => 'required|date',
        ]);

        $declaration = Hslb::findOrFail($id);
        $declaration->update([
            'hr_comment' => $request->hr_comment,
            'hr_member_id' => auth()->id(), // Assuming hr_member_id is the column for HR member's ID
            'hr_signature' => $request->hr_signature,
            'hr_date' => $request->hr_date,
        ]);

        return redirect()->route('hslb.index')->with('success', 'HR confirmation submitted successfully.');
    }
}
