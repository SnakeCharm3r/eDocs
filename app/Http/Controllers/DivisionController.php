<?php

namespace App\Http\Controllers;
use App\Models\Division;
use App\Models\Departments;

use Illuminate\Http\Request;

class DivisionController extends Controller
{
        //validate all the fields
        public function index()
        {
            $user = auth()->user();
            $departments = Departments::all();
            $divisions = Division::with('departments')
                ->where(function($query) {
                    $query->where('delete_status', '!=', '1')
                          ->orWhereNull('delete_status');
                })
                ->get();
            return view('Divisions.index', compact('divisions', 'departments'));
        }

        //Show all divisions
        public function create()
        {
            //return the users values and the divisions view
            $departments = Departments::all();
            $user = auth()->user();
            return view('Divisions.create', compact('departments', 'user'));
        }
        
        public function store(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'departments' => 'required|array|min:1',
                'departments.*' => 'exists:departments,id',
                'description' => 'nullable|string|max:1000',
                'code' => 'required|string|max:50|unique:divisions,code',
                'status' => 'required|in:active,inactive',
            ]);
            
            $division = Division::create([
                'name' => $request->name,
                'description' => $request->description,
                'code' => $request->code,
                'status' => $request->status,
            ]);

            // Attach departments to division
            if ($division && $request->has('departments')) {
                $division->departments()->sync($request->departments);
            }

            if($division) {
                return redirect()->route('division.index')->with('success', 'Entity created successfully!');
            } else {
                return redirect()->back()->with('error', 'Entity could not be created.');
            }
        }

        public function edit($id)
        {
            $division = Division::with('departments')->findOrFail($id);
            $departments = Departments::all();
            return view('Divisions.edit', compact('division', 'departments'));
        }

        public function update(Request $request, $id)
        {
            $division = Division::findOrFail($id);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'departments' => 'required|array|min:1',
                'departments.*' => 'exists:departments,id',
                'description' => 'nullable|string|max:1000',
                'code' => 'required|string|max:50|unique:divisions,code,' . $id,
                'status' => 'required|in:active,inactive',
            ]);
            
            $division->update([
                'name' => $request->name,
                'description' => $request->description,
                'code' => $request->code,
                'status' => $request->status,
            ]);

            // Sync departments
            $division->departments()->sync($request->departments);

            return redirect()->route('division.index')->with('success', 'Entity updated successfully!');
        }

        public function destroy($id)
        {
            $division = Division::with('departments')->findOrFail($id);
            
            // Get department count
            $departmentsCount = $division->departments ? $division->departments->count() : 0;
            
            // Prevent deletion if entity has departments assigned
            if ($departmentsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete entity. There are ' . $departmentsCount . ' department(s) assigned to this entity. Please remove all departments first before deleting.'
                ], 400);
            }

            // Soft delete by setting delete_status to 1
            $division->update([
                'delete_status' => '1'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Entity deleted successfully!'
            ]);
        }
}

