<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\AfterSalesServiceRequest;
use App\Models\AfterSalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AfterSalesServiceController extends Controller
{
    public function index(Request $request): View
    {
        $servicesQuery = AfterSalesService::query()->latest();
        $search = $request->string('search')->trim();

        if ($search->isNotEmpty()) {
            $needle = $search->value();
            $servicesQuery->where(function ($query) use ($needle) {
                $query->where('customer_name', 'like', '%' . $needle . '%')
                    ->orWhere('coordinator_name', 'like', '%' . $needle . '%')
                    ->orWhere('coordinator_mobile', 'like', '%' . $needle . '%')
                    ->orWhere('address', 'like', '%' . $needle . '%')
                    ->orWhere('issue_description', 'like', '%' . $needle . '%');
            });
        }

        $services = $servicesQuery->paginate(12)->withQueryString();

        return view('support.after-sales-services.index', [
            'services' => $services,
            'search' => $search->value(),
        ]);
    }

    public function create(): View
    {
        return view('support.after-sales-services.create');
    }

    public function store(AfterSalesServiceRequest $request): RedirectResponse
    {
        $service = AfterSalesService::create($request->validated() + [
            'created_by_id' => auth()->id(),
        ]);

        return redirect()
            ->route('support.after-sales-services.show', $service)
            ->with('success', 'فرم خدمات پس از فروش با موفقیت ثبت شد.');
    }

    public function show(AfterSalesService $afterSalesService): View
    {
        return view('support.after-sales-services.show', compact('afterSalesService'));
    }

    public function edit(AfterSalesService $afterSalesService): View
    {
        return view('support.after-sales-services.edit', compact('afterSalesService'));
    }

    public function update(AfterSalesServiceRequest $request, AfterSalesService $afterSalesService): RedirectResponse
    {
        $afterSalesService->update($request->validated());

        return redirect()
            ->route('support.after-sales-services.show', $afterSalesService)
            ->with('success', 'فرم خدمات پس از فروش با موفقیت بروزرسانی شد.');
    }

    public function destroy(AfterSalesService $afterSalesService): RedirectResponse
    {
        $afterSalesService->delete();

        return redirect()
            ->route('support.after-sales-services.index')
            ->with('success', 'فرم خدمات پس از فروش حذف شد.');
    }
}
