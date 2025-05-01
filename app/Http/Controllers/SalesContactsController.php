<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesContactsController extends Controller
{
    public function index()
    {
        return view('sales.contacts.index');
    }
} 