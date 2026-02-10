<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Crud\Crud;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class OpportunityFavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        return Crud::index('opportunities', $request);
    }

    public function store(Request $request, Opportunity $opportunity)
    {
        $request->user()->favoriteOpportunities()->syncWithoutDetaching([$opportunity->id]);

        return back()->with('success', 'فرصت فروش به علاقه‌مندی‌ها اضافه شد.');
    }

    public function destroy(Request $request, Opportunity $opportunity)
    {
        $request->user()->favoriteOpportunities()->detach($opportunity->id);

        return back()->with('success', 'فرصت فروش از علاقه‌مندی‌ها حذف شد.');
    }
}
