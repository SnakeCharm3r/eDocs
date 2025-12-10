<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LanguageKnowledge;
use Illuminate\Support\Facades\Auth;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;

class LanguageKnowledgeController extends Controller
{
    /**
     * Display a listing of the language knowledge for the authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $languageKnowledge = LanguageKnowledge::where('userId', $user->id)->get();

        return view('language.index', compact('languageKnowledge', 'user'));
    }

    /**
     * Store a newly created language knowledge in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addLanguageKnowledge(Request $request)   
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:255',
            'speaking' => 'required|string|max:255',
            'reading' => 'required|string|max:255',
            'writing' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if the user already has 4 language knowledge entries
        $languageCount = LanguageKnowledge::where('userId', Auth::id())->count();

        if ($languageCount >= 4) {
            return redirect()->back()->with('error', 'You can only add up to 4 language knowledge entries.')->withInput();
        }

        LanguageKnowledge::create([
            'userId' => Auth::id(),
            'language' => $request->input('language'),
            'speaking' => $request->input('speaking'),
            'reading' => $request->input('reading'),
            'writing' => $request->input('writing'),
            'delete_status' => 0,
        ]);

        Alert::success('Successful', 'Language knowledge added successfully');
        return redirect()->route('language_knowledge.index')->with('success', 'Language knowledge added successfully.');
    }


    /**
     * Show the form for editing the specified language knowledge.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $languageKnowledge = LanguageKnowledge::findOrFail($id);

        // Check if the authenticated user owns this language knowledge
        if ($languageKnowledge->userId != Auth::id()) {
            return redirect()->route('language_knowledge.index')->with('error', 'Unauthorized access.');
        }
        Alert::success('Successful', 'Language knowledge updated successfully');
        return view('profile.edit_language', compact('languageKnowledge'));
    }

    /**
     * Update the specified language knowledge in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'language' => 'required|string|max:255',
            'speaking' => 'required|string|max:255',
            'reading' => 'required|string|max:255',
            'writing' => 'required|string|max:255',
        ]);
        //dd(1234);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }


        $languageKnowledge = LanguageKnowledge::findOrFail($id);


        $languageKnowledge->update([
            'language' => $request->input('language'),
            'speaking' => $request->input('speaking'),
            'reading' => $request->input('reading'),
            'writing' => $request->input('writing'),
        ]);
        Alert::success('Successful', 'Language knowledge updated successfully');
        return redirect()->route('language_knowledge.index')->with('success', 'Language knowledge updated successfully.');
    }

    /**
     * Remove the specified language knowledge from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $languageKnowledge = LanguageKnowledge::findOrFail($id);

        // Check if the authenticated user owns this language knowledge
        if ($languageKnowledge->userId != Auth::id()) {
            return redirect()->route('language_knowledge.index')->with('error', 'Unauthorized access.');
        }

        $languageKnowledge->delete();
        Alert::success('Successful', 'Language knowledge deleted successfully');
        return redirect()->route('language_knowledge.index')->with('success', 'Language knowledge deleted successfully.');
    }
}
