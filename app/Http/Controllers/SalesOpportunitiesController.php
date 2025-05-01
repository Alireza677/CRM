<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalesOpportunitiesController extends Controller
{
    public function index()
    {
        return view('sales.opportunities.index');
    }
} 