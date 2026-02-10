<?php

namespace App\Http\Controllers;

use App\Models\ShiftSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShiftSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // add ->middleware('can:manage shifts') if you wire a policy/permission
    }

    public function index()
    {
        $shifts = ShiftSetting::orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('shift_settings.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        // auto-fill weekly_hours if not provided
        if (!isset($data['weekly_hours']) || $data['weekly_hours'] === null) {
            $data['weekly_hours'] = $this->calcWeekly($data['days_per_week'] ?? null, $data['hours_per_day'] ?? null);
        }

        $shift = ShiftSetting::create($data);

        if ($request->wantsJson()) {
            return response()->json(['status' => 200, 'message' => 'Shift created.', 'data' => $shift]);
        }

        return redirect()->route('shift-settings.index')->with('success', 'Shift created.');
    }

    public function edit(ShiftSetting $shift_setting)
    {
        return view('shift_settings.edit', ['shift' => $shift_setting]);
    }

    public function update(Request $request, ShiftSetting $shift_setting)
    {
        $data = $this->validated($request, $shift_setting->id);

        if (!isset($data['weekly_hours']) || $data['weekly_hours'] === null) {
            $data['weekly_hours'] = $this->calcWeekly($data['days_per_week'] ?? null, $data['hours_per_day'] ?? null);
        }

        $shift_setting->update($data);

        if ($request->wantsJson()) {
            return response()->json(['status' => 200, 'message' => 'Shift updated.', 'data' => $shift_setting]);
        }

        return redirect()->route('shift-settings.index')->with('success', 'Shift updated.');
    }

    public function destroy(ShiftSetting $shift_setting)
    {
        // Check if shift is being used (you may need to add relationships if shifts are referenced elsewhere)
        // For now, we'll allow deletion but you can add checks here if needed
        
        $shift_setting->delete();
        
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift deleted successfully!'
            ]);
        }
        
        return back()->with('success', 'Shift deleted.');
    }

    private function calcWeekly($days, $hoursPerDay): ?float
    {
        $d = (int)($days ?? 0);
        $h = (float)($hoursPerDay ?? 0);
        return ($d > 0 && $h > 0) ? round($d * $h, 2) : null;
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'code'            => ['nullable', 'string', 'max:20', Rule::unique('shift_settings', 'code')->ignore($ignoreId)],
            'is_official_duty' => ['sometimes', 'boolean'],
            'start_time'      => ['nullable', 'date_format:H:i'],
            'end_time'        => ['nullable', 'date_format:H:i'],
            'days_per_week'   => ['nullable', 'integer', 'min:1', 'max:7'],
            'hours_per_day'   => ['nullable', 'numeric', 'min:0', 'max:24'],
            'weekly_hours'    => ['nullable', 'numeric', 'min:0', 'max:168'],
            'is_active'       => ['sometimes', 'boolean'],
            'description'     => ['nullable', 'string', 'max:1000'],
        ], [], [
            'is_official_duty' => 'official duty',
        ]);
    }
}
