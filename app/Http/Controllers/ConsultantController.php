<?php

namespace App\Http\Controllers;

use App\Models\ConsultantModel;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConsultantController extends Controller
{
    public  function __construct()
    {
      $this->middleware('auth:sanctum');
    }

public function index()
{
    $consult = ConsultantModel::all();
    // dd($consult); // Add this line temporarily
    return view('contracts.consultant.index', compact('consult'));
}

   public function create(){
    $dept = Departments::all();

    return view('contracts.consultant.create', compact('dept'));
   }

   public function store(Request $request)
  {
    $user = Auth::user();

    $validator = Validator::make($request->all(), [
        'application_date' => 'required|date',
        'consultant_full_name' => 'required|string|max:255',
        'consultant_address' => 'required|string',
        'consultant_mobile' => 'required|string|max:20',
        'qualification' => 'required|string',
        'year_of_experience' => 'required|numeric|min:0',
        'hosted_dept' => 'required|exists:departments,id',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after:startDate',
    ]);

    if ($validator->fails()) {
        return redirect()
            ->back()
            ->withErrors($validator)
            ->withInput();
    }

     try {
         $consultant = ConsultantModel::create([
            'application_date' => $request->application_date,
            'consultant_full_name' => $request->consultant_full_name,
            'consultant_address' => $request->consultant_address,
            'consultant_mobile' => $request->consultant_mobile,
            'qualification' => $request->qualification,
            'year_of_experience' => $request->year_of_experience,
            'hosted_dept' => $request->hosted_dept,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'createdBy' => $user->id,
         ]);

         // Return to index view with toast success message
         return redirect()
            ->route('consultants.index')
            ->with('toast', [
                'type' => 'success',
                'message' => 'Consultant created successfully!'
            ]);

          } catch (\Exception $e) {
         \Log::error('Error creating consultant: ' . $e->getMessage());

         return redirect()
            ->back()
            ->with('toast', [
                'type' => 'error',
                'message' => 'Failed to create consultant. Please try again.'
            ])
            ->withInput();
     }
   }
}
