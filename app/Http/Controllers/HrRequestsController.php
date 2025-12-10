<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HrRequestsController extends Controller
{

    public function index()
    {
        return view('request-hr-forms.index');
    }
}
