<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Departments; // your plural model
use App\Models\Platform;

class DepartmentPlatformController extends Controller
{
    // Return the table of platforms with switches for a given department (AJAX)
    public function modal(Departments $department)
    {
        $platforms   = Platform::orderBy('name')->get();
        $attachedIds = $department->platforms()->pluck('platform_id')->toArray();

        return view('department.platforms._modal_list', compact('department', 'platforms', 'attachedIds'));
    }

    // Attach / detach a platform to/from a department (AJAX)
    public function toggle(Request $request, Departments $department)
    {
        $data = $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'attach'      => 'required|boolean',
        ]);

        if ($data['attach']) {
            $department->platforms()->syncWithoutDetaching([$data['platform_id']]);
        } else {
            $department->platforms()->detach($data['platform_id']);
        }

        return response()->json([
            'status'  => 200,
            'message' => $data['attach'] ? 'Platform attached.' : 'Platform detached.',
        ]);
    }

    public function myPlatforms()
    {
        $user = auth()->user();
        if (!$user || !$user->deptId) {
            return response()->json(['platforms' => []]);
        }

        // Platforms mapped to the user's department
        $dept = \App\Models\Departments::with('platforms:id,name')
            ->find($user->deptId);

        return response()->json([
            'platforms' => $dept?->platforms?->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])->values() ?? []
        ]);
    }
    // public function unitsJson(\App\Models\Platform $platform)
    // {
    //     // If your units have an "is_active" flag, filter it here.
    //     $units = $platform->units()
    //         ->select('id', 'name', 'is_active')
    //         ->orderBy('name')
    //         ->get()
    //         ->map(fn($u) => [
    //             'id' => $u->id,
    //             'name' => $u->name . ($u->is_active ? '' : ' (inactive)'),
    //             'active' => (bool) $u->is_active,
    //         ]);

    //     return response()->json(['units' => $units]);
    // }

    public function unitsJson(\App\Models\Platform $platform)
    {
        $units = $platform->units()
            ->select('id', 'name')   // ← no is_active
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'   => $u->id,
                'name' => $u->name,
            ]);

        return response()->json(['units' => $units]);
    }
}
