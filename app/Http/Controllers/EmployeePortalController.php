<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeePortalController extends Controller
{
    public function index()
    {
        $breadcrumb = [
            ['title' => 'پرتال کارمند'],
        ];
        return view('employee-portal.index', compact('breadcrumb'));
    }

    public function contract()
    {
        $breadcrumb = [
            ['title' => 'پرتال کارمند', 'url' => route('employee.portal.index')],
            ['title' => 'قرارداد من'],
        ];
        return view('employee-portal.contract', compact('breadcrumb'));
    }

    public function leaveRequest()
    {
        $breadcrumb = [
            ['title' => 'پرتال کارمند', 'url' => route('employee.portal.index')],
            ['title' => 'درخواست مرخصی'],
        ];
        return view('employee-portal.leave-request', compact('breadcrumb'));
    }

    public function submitLeaveRequest(Request $request)
    {
        $data = $request->validate([
            'from_date' => ['required', 'date'],
            'to_date'   => ['required', 'date', 'after_or_equal:from_date'],
            'reason'    => ['required', 'string', 'max:500'],
        ]);

        // Placeholder: integrate with Leave model/workflow later
        // For now, just flash a success message for demo purposes
        return redirect()
            ->route('employee.portal.leaves')
            ->with('success', 'درخواست مرخصی با موفقیت ثبت شد.');
    }

    public function leaves()
    {
        $breadcrumb = [
            ['title' => 'پرتال کارمند', 'url' => route('employee.portal.index')],
            ['title' => 'لیست مرخصی‌ها'],
        ];
        // In future: load user leaves from DB
        return view('employee-portal.leaves', compact('breadcrumb'));
    }

    public function payslips()
    {
        $breadcrumb = [
            ['title' => 'پرتال کارمند', 'url' => route('employee.portal.index')],
            ['title' => 'فیش‌های حقوقی'],
        ];
        return view('employee-portal.payslips', compact('breadcrumb'));
    }

    public function insurance()
    {
        $breadcrumb = [
            ['title' => 'پرتال کارمند', 'url' => route('employee.portal.index')],
            ['title' => 'وضعیت بیمه'],
        ];
        return view('employee-portal.insurance', compact('breadcrumb'));
    }
}
