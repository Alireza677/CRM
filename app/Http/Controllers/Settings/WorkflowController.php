<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;

class WorkflowController extends Controller
{
    public function index()
    {
        return view('settings.workflows.index');
    }
}

