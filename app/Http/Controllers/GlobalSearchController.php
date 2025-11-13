<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema; // مهم
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\Proforma;
use App\Models\SalesLead;

class GlobalSearchController extends Controller
{
    /** کمک‌متد: اگر ستونی وجود داشت، orWhere بزن */
    private function likeIfExists($query, string $table, string $col, string $q)
    {
        if (Schema::hasColumn($table, $col)) {
            $query->orWhere($col, 'like', "%{$q}%");
        }
    }

    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        /* ===================== Leads ===================== */
        $leads = SalesLead::query()
            ->when($q, function ($qq) use ($q) {
                $table = (new SalesLead)->getTable();
                $qq->where(function ($w) use ($table, $q) {
                    $this->likeIfExists($w, $table, 'full_name', $q);
                    $this->likeIfExists($w, $table, 'company', $q);
                    $this->likeIfExists($w, $table, 'email', $q);
                    $this->likeIfExists($w, $table, 'mobile', $q);
                    $this->likeIfExists($w, $table, 'phone', $q);
                    $this->likeIfExists($w, $table, 'lead_source', $q);
                    $this->likeIfExists($w, $table, 'lead_status', $q);
                });
            })
            ->limit(20)->get()
            ->map(function ($lead) {
                $person = trim(((string)($lead->prefix ? $lead->prefix . ' ' : '')) . ((string)($lead->full_name ?? '')));
                $lead->title    = $person !== '' ? $person : ($lead->company ?: ('Lead #'.$lead->id));
                $lead->summary  = $lead->notes ?? null;
                $lead->phone    = $lead->phone ?? $lead->mobile ?? null;
                $lead->email    = $lead->email ?? null;
                $lead->show_url = route('sales.leads.show', $lead);
                return $lead;
            });

        /* ===================== Contacts ===================== */
        $contacts = Contact::query()
            ->with(['organization:id,name']) // اگر چنین رابطه‌ای ندارید حذف کنید
            ->when($q, function ($qq) use ($q) {
                $table = (new Contact)->getTable();
                $qq->where(function ($w) use ($table, $q) {
                    $this->likeIfExists($w, $table, 'name', $q);
                    $this->likeIfExists($w, $table, 'full_name', $q);
                    $this->likeIfExists($w, $table, 'first_name', $q);
                    $this->likeIfExists($w, $table, 'last_name', $q);
                    $this->likeIfExists($w, $table, 'phone', $q);
                    $this->likeIfExists($w, $table, 'mobile', $q);
                    $this->likeIfExists($w, $table, 'email', $q);
                });
            })
            ->limit(20)->get()
            ->map(function ($c) {
                // عنوان ایمن با توجه به فیلدهای موجود
                $title = $c->name
                    ?? $c->full_name
                    ?? trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                $c->title    = $title !== '' ? $title : ('Contact #'.$c->id);

                // یادداشت/توضیح اختیاری
                $c->summary  = $c->notes ?? $c->description ?? null;

                // شماره/ایمیل (هر کدام که دارید)
                $c->phone    = $c->phone ?? $c->mobile ?? null;
                $c->email    = $c->email ?? null;

                // لینک‌ها را با روت‌های پروژه‌ات هماهنگ کن
                // Use correct route names within the 'sales' group
                $c->show_url = route('sales.contacts.show', $c);
                $c->edit_url = route('sales.contacts.edit', $c);
                return $c;
            });

        /* ===================== Organizations ===================== */
        $organizations = Organization::query()
            ->when($q, function ($qq) use ($q) {
                $table = (new Organization)->getTable();
                $qq->where(function ($w) use ($table, $q) {
                    $this->likeIfExists($w, $table, 'name', $q);
                    $this->likeIfExists($w, $table, 'title', $q);
                    $this->likeIfExists($w, $table, 'description', $q);
                });
            })
            ->limit(20)->get()
            ->map(function ($o) {
                $o->title    = $o->name ?? $o->title ?? ('Org #'.$o->id);
                $o->summary  = $o->description ?? null;
                // Use correct route names within the 'sales' group
                $o->show_url = route('sales.organizations.show', $o);
                $o->edit_url = route('sales.organizations.edit', $o);
                return $o;
            });

        /* ===================== Opportunities ===================== */
        $opportunities = Opportunity::query()
            ->with(['assignedTo:id,name','organization:id,name'])
            ->when($q, function ($qq) use ($q) {
                $table = (new Opportunity)->getTable();
                $qq->where(function ($w) use ($table, $q) {
                    $this->likeIfExists($w, $table, 'subject', $q);
                    $this->likeIfExists($w, $table, 'title', $q);
                    $this->likeIfExists($w, $table, 'stage', $q);
                });
            })
            ->limit(20)->get()
            ->map(function ($op) {
                $op->title    = $op->subject ?? $op->title ?? ('Opportunity #'.$op->id);
                $op->amount   = $op->amount ?? $op->value ?? $op->expected_revenue ?? null;
                $op->status   = $op->stage ?? null;
                $op->show_url = route('sales.opportunities.show', $op);
                $op->edit_url = route('sales.opportunities.edit', $op);
                return $op;
            });

        /* ===================== Proformas ===================== */
        $proformas = Proforma::query()
            ->with(['assignedTo:id,name','organization:id,name'])
            ->when($q, function ($qq) use ($q) {
                $table = (new Proforma)->getTable();
                $qq->where(function ($w) use ($table, $q) {
                    $this->likeIfExists($w, $table, 'subject', $q);
                    $this->likeIfExists($w, $table, 'title', $q);
                    // جستجو بر اساس شماره هم
                    $w->orWhere($table.'.id', $q)->orWhere($table.'.id', 'like', "%{$q}%");
                });
            })
            ->limit(20)->get()
            ->map(function ($pf) {
                $pf->title    = $pf->subject ?? $pf->title ?? ('PF-'.$pf->id);
                $pf->total    = $pf->total_amount ?? $pf->total ?? $pf->amount ?? null;
                $pf->status   = $pf->approval_stage ?? $pf->proforma_stage ?? null;
                $pf->show_url = route('sales.proformas.show', $pf);
                $pf->edit_url = route('sales.proformas.edit', $pf);
                return $pf;
            });

        $results = collect([
            'leads'         => $leads,
            'contacts'      => $contacts,
            'organizations' => $organizations,
            'opportunities' => $opportunities,
            'proformas'     => $proformas,
        ]);

        return view('global-search.index', compact('results'));
    }
}
