<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashbordController extends Controller
{

    public function index()
    {
        return view('login');
    }

    public function registar()
    {
        return view('registar');
    }
}
