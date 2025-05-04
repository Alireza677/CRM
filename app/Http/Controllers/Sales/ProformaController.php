<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Proforma;
use App\Models\User;
use Illuminate\Http\Request;

class ProformaController extends Controller
{
    public function index(Request $request)
    {
        $query = Proforma::query()
            ->select('proformas.*', 'users.name as assigned_to_name', 'organizations.name as organization_name', 
                    'contacts.name as contact_name', 'opportunities.name as opportunity_name')
            ->leftJoin('users', 'proformas.assigned_to', '=', 'users.id')
            ->leftJoin('organizations', 'proformas.organization_id', '=', 'organizations.id')
            ->leftJoin('contacts', 'proformas.contact_id', '=', 'contacts.id')
            ->leftJoin('opportunities', 'proformas.opportunity_id', '=', 'opportunities.id');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('proformas.subject', 'like', "%{$search}%")
                  ->orWhere('organizations.name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Handle special cases for sorting
        if ($sortField === 'assigned_to_name') {
            $query->orderBy('users.name', $sortDirection);
        } elseif ($sortField === 'organization_name') {
            $query->orderBy('organizations.name', $sortDirection);
        } elseif ($sortField === 'contact_name') {
            $query->orderBy('contacts.name', $sortDirection);
        } elseif ($sortField === 'opportunity_name') {
            $query->orderBy('opportunities.name', $sortDirection);
        } else {
            $query->orderBy("proformas.{$sortField}", $sortDirection);
        }

        $proformas = $query->paginate(10)->withQueryString();

        return view('sales.proformas.index', compact('proformas'));
    }
} 