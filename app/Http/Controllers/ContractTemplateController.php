<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContractTemplate;

class ContractTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = ContractTemplate::all();
        return view('contract-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('contract-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template = ContractTemplate::create($validated);

        return redirect()->route('contract-templates.edit', $template)
            ->with('success', 'Template created successfully');
    }

public function edit(ContractTemplate $contractTemplate)
{
    return view('contract-templates.edit', ['template' => $contractTemplate]);
}


    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $contractTemplate->update($validated);

        return redirect()->route('contract-templates.index')
            ->with('success', 'Template updated successfully');
    }
}
