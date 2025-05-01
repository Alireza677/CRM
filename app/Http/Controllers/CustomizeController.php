<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomizeController extends Controller
{
    public function index()
    {
        return view('customize.index');
    }
} 