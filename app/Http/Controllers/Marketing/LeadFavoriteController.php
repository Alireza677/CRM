<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\LeadsBreadcrumbs;
use App\Models\SalesLead;
use Illuminate\Http\Request;

class LeadFavoriteController extends Controller
{
    use LeadsBreadcrumbs;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $leads = $user->favoriteLeads()
            ->with('assignedUser')
            ->orderByDesc('lead_favorites.created_at')
            ->paginate(10);

        return view('marketing.leads.favorites', compact('leads'))
            ->with('breadcrumb', $this->leadsBreadcrumb([
                ['title' => 'علاقه‌مندی‌ها'],
            ]));
    }

    public function store(Request $request, SalesLead $lead)
    {
        $request->user()->favoriteLeads()->syncWithoutDetaching([$lead->id]);

        return back()->with('success', 'سرنخ به علاقه‌مندی‌ها اضافه شد.');
    }

    public function destroy(Request $request, SalesLead $lead)
    {
        $request->user()->favoriteLeads()->detach($lead->id);

        if ($request->input('redirect_to') === 'favorites') {
            return redirect()->route('marketing.leads.favorites.index')
                ->with('success', 'سرنخ از علاقه‌مندی‌ها حذف شد.');
        }

        return back()->with('success', 'سرنخ از علاقه‌مندی‌ها حذف شد.');
    }
}
