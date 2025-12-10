<?php

namespace App\Http\Controllers;

use App\Models\Hec;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;


class HecController extends Controller
{

     public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(){
        $hec = Hec::all();

       // dd($hec);
        return view('hec.index', compact('hec'));
    }

    public function create()
    {
        return view('hec.create');
    }

    public function addHec(Request $request){
      $validator = Validator::make($request->all(), [
        'hec_level_name' => 'required',
        'descriptions' => 'required',
      ]);
      //dd($request);

      if($validator->fails()){
        return response()->json([
            'status' => 400,
            'errors' => $validator->errors(),
        ]);
        }

        $hecCheck = Hec::where('hec_level_name', $request->hec_level_name)->first();
        if($hecCheck){
            return response()->json([
                'status' => 400,
                'message' => 'HEC level is already exist',
                'data' => $request->all()
            ]);

          }
        $hec = Hec::create([
            'hec_level_name' => $request->input('hec_level_name'),
            'descriptions' => $request->input('descriptions'),
            'createdBy' => auth()->id(),
        ]);

        Alert::success('successful','HEC Added');
        return redirect()->route('hec.index')->with('success', 'HEC added successfully.');
    }

    public function edit(string $id)
    {
        $hecs = Hec::findOrFail($id);
        return view('hec.edit', compact('hecs'));
    }


    public function update(Request $request, string $id)
    {
        $hec = Hec::find($id);
          if(!$hec){
            return response()->json([
                'status' => 404,
                'message' => 'HEC Level not found',
            ], 404);
          }

          $validator = Validator::make($request->all(), [
            'hec_level_name' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->errors(),
            ]);
        }

        $hec = Hec::findOrFail($id);
        $hec->update([
            'hec_level_name' => $request->input('hec_level_name'),
            'description' => $request->input('description'),
            'updatedBy' =>auth()->id(),
        ]);

        Alert::success('successful','HEC Level updated');
        return redirect()->route('hec.index')->with('success', 'HEC Level updated successfully.');
    }


}
