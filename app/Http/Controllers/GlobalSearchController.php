<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Organization;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');

        $contacts = Contact::where('first_name', 'like', "%{$query}%")
            ->orWhere('last_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->take(10)->get();

        $opportunities = Opportunity::where('name', 'like', "%{$query}%")
            ->take(10)->get();

        $organizations = Organization::where('name', 'like', "%{$query}%")
            ->take(10)->get();

        return view('search.index', compact('query', 'contacts', 'opportunities', 'organizations'));
    }
}
