<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Opportunity;
use App\Models\PurchaseOrder;
use App\Models\Proforma;
use App\Helpers\DateHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;


class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // عمداً authorizeResource/Policy نداریم تا رد پیش‌فرضِ دسترسی رخ ندهد
    }

    /**
     * لیست اسناد
     */
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();

        $query = Document::visibleFor($user, 'documents')
            ->with(['opportunity','purchaseOrder','user']);

        $documents = $this->restrictDocumentsByCategory($query, $user)
            ->latest()
            ->paginate(10);

        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => route('dashboard')],
            ['title' => 'اسناد'],
        ];

        return view('sales.documents.index', compact('documents', 'breadcrumb'));
    }

    /**
     * فرم ایجاد
     */
    public function create(Request $request)
    {
        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => route('dashboard')],
            ['title' => 'اسناد', 'url' => route('sales.documents.index')],
            ['title' => 'ایجاد سند'],
        ];

        // اگر timestamps نداری از orderByDesc('id') استفاده کن
        $opportunities = Opportunity::select('id','name')->latest()->get();

        // فرصت پیش‌فرض وقتی از صفحه خودش می‌آییم
        $defaultOpportunityId = null;
        $defaultOpportunityName = null;
        if ($request->filled('opportunity_id')) {
            $id = (int) $request->query('opportunity_id');
            $op = Opportunity::select('id','name')->find($id);
            if ($op) {
                $defaultOpportunityId = $op->id;
                $defaultOpportunityName = $op->name;
            }
        }

        $defaultPurchaseOrderId = null;
        $defaultPurchaseOrderSubject = null;
        if ($request->filled('purchase_order_id')) {
            $poId = (int) $request->query('purchase_order_id');
            $po = PurchaseOrder::select('id','subject')->find($poId);
            if ($po) {
                $defaultPurchaseOrderId = $po->id;
                $defaultPurchaseOrderSubject = $po->subject ?: ('سفارش خرید شماره ' . $po->id);
            }
        }

        return view(
            'sales.documents.create',
            compact(
                'breadcrumb',
                'opportunities',
                'defaultOpportunityId',
                'defaultOpportunityName',
                'defaultPurchaseOrderId',
                'defaultPurchaseOrderSubject'
            )
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'             => ['required','string','max:255'],
            'file'              => ['required','file','max:10240','mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'opportunity_id'    => ['nullable','integer','exists:opportunities,id'],
            'purchase_order_id' => ['nullable','integer','exists:purchase_orders,id'],
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $data = [
            'title'             => $request->input('title'),
            'file_path'         => $path,
            'opportunity_id'    => $request->integer('opportunity_id') ?: null,
            'purchase_order_id' => $request->integer('purchase_order_id') ?: null,
        ];

        if (Schema::hasColumn('documents', 'user_id')) {
            $data['user_id'] = $request->user()->id;
        }

        $document = Document::create($data);

        if ($document->opportunity_id) {
            $relatedProformas = Proforma::where('opportunity_id', $document->opportunity_id)->get();

            foreach ($relatedProformas as $proforma) {
                $logTime = now();
                $actorName = auth()->user()->name ?? 'سیستم';
                $description = $actorName . ' سند جدیدی را در تاریخ ' . DateHelper::toJalali($logTime, 'H:i Y/m/d') . ' برای این پیش‌فاکتور بارگذاری کرد.';

                activity('proforma')
                    ->performedOn($proforma)
                    ->causedBy(auth()->user())
                    ->event('document_uploaded')
                    ->withProperties([
                        'document_id' => $document->id,
                        'document_title' => $document->title,
                        'opportunity_id' => $document->opportunity_id,
                    ])
                    ->log($description);
            }
        }

        if ($document->purchase_order_id) {
            try {
                $purchaseOrder = PurchaseOrder::find($document->purchase_order_id);
            } catch (\Throwable $e) {
                $purchaseOrder = null;
            }

            if ($purchaseOrder) {
                activity('purchase_order')
                    ->performedOn($purchaseOrder)
                    ->causedBy($request->user())
                    ->event('document_added')
                    ->withProperties([
                        'document' => [
                            'id' => $document->id,
                            'title' => $document->title,
                            'file_path' => $document->file_path,
                            'extension' => pathinfo($document->file_path ?? '', PATHINFO_EXTENSION),
                        ],
                    ])
                    ->log('document_added');
            }
        }

        // اگر از صفحه یک فرصت آمده‌ایم، برگرد همانجا (UX بهتر)
        if ($document->opportunity_id) {
            return redirect()
                ->route('sales.opportunities.show', $document->opportunity_id)
                ->with('success', 'سند برای این فرصت ثبت شد.');
        }

        if ($document->purchase_order_id) {
            return redirect()
                ->route('inventory.purchase-orders.show', $document->purchase_order_id)
                ->with('success', 'سند برای این سفارش خرید ثبت شد.');
        }

        return redirect()
            ->route('sales.documents.index')
            ->with('success', 'سند با موفقیت ذخیره شد.');
    }

    /**
     * مشاهده (stream) در مرورگر — جایگزین asset('storage/...') برای جلوگیری از 500
     */
    public function view(Document $document)
    {
        $this->authorize('view', $document);

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'فایل یافت نشد.');
        }

        $disk = Storage::disk('public');
        $absolutePath = $disk->path($document->file_path);

        // 1) Try Storage mimeType, 2) fallback to File::mimeType, 3) fallback by extension
        $mime = $disk->mimeType($document->file_path) ?: null;
        if (!$mime && \Illuminate\Support\Facades\File::exists($absolutePath)) {
            $mime = @\Illuminate\Support\Facades\File::mimeType($absolutePath) ?: null;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $fallback = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        if (!$mime && isset($fallback[$ext])) {
            $mime = $fallback[$ext];
        }
        if (!$mime) { $mime = 'application/octet-stream'; }

        $downloadName = str($document->title)->slug('_').'.'.$ext;
        Log::info('doc.view', ['id' => $document->id, 'mime' => $mime, 'ext' => $ext]);

        $stream = $disk->readStream($document->file_path);
        if ($stream === false) {
            abort(404, 'امکان خواندن فایل وجود ندارد.');
        }

        if (function_exists('ob_get_level') && ob_get_level()) {
            @ob_end_clean();
        }

        $size = $disk->size($document->file_path);

        return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Content-Length' => is_numeric($size) ? (string)$size : null,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * دانلود فایل
     */
    public function download(Document $document)
    {
        $this->authorize('download', $document);
        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'فایل یافت نشد.');
        }

        $downloadName = str($document->title)->slug('_').'.'.pathinfo($document->file_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($document->file_path, $downloadName);
    }

    /**
     * فرم ویرایش
     */
    public function edit(Document $document)
    {
        $this->authorizeManageDocument($document); // ⬅ فقط مالک/ادمین

        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => route('dashboard')],
            ['title' => 'اسناد', 'url' => route('sales.documents.index')],
            ['title' => 'ویرایش سند'],
        ];

        // Use alias to keep compatibility if views expect 'title'
        $opportunities = Opportunity::select(['id', \DB::raw('name as title')])->latest()->get();

        return view('sales.documents.edit', compact('document','breadcrumb','opportunities'));
    }

    /**
     * بروزرسانی سند (فایل اختیاری)
     */
    public function update(Request $request, Document $document)
    {
        $this->authorizeManageDocument($document); // ⬅ فقط مالک/ادمین

        $request->validate([
            'title'             => ['required','string','max:255'],
            'file'              => ['nullable','file','max:10240', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/gif,image/webp,image/svg+xml'],
            'opportunity_id'    => ['nullable','integer','exists:opportunities,id'],
            'purchase_order_id' => ['nullable','integer','exists:purchase_orders,id'],
        ]);

        $updates = [
            'title'             => $request->input('title'),
            'opportunity_id'    => $request->input('opportunity_id'),
            'purchase_order_id' => $request->input('purchase_order_id'),
        ];

        if ($request->hasFile('file')) {
            // حذف فایل قبلی (اگر وجود دارد)
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            $updates['file_path'] = $request->file('file')->store('documents', 'public');
        }

        $document->update($updates);

        return redirect()
            ->route('sales.documents.index')
            ->with('success', 'سند بروزرسانی شد.');
    }

    /**
     * حذف سند
     */
    public function destroy(Document $document)
    {
        $this->authorizeManageDocument($document); // ⬅ فقط مالک/ادمین

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()
            ->route('sales.documents.index')
            ->with('success', 'سند حذف شد.');
    }

    /**
     * مجوز ساده: فقط لاگین بودن/درصورت نیاز مالکیت.
     * اگر خواستی شرط‌های بیشتر بگذاری اینجاست.
     */
    private function authorizeViewDocument(Document $document): void
    {
        // فقط لاگین بودن کافی است؛ نیازی به مالکیت نیست
        if (!auth()->check()) {
            abort(403, 'برای مشاهده باید وارد شوید.');
        }
    
        // اگر بعداً خواستی حالت خصوصی داشته باشی:
        // if (Schema::hasColumn('documents', 'is_private') && $document->is_private) {
        //     $this->authorizeManageDocument($document); // فقط مالک/ادمین
        // }
    }
    private function authorizeManageDocument(Document $document): void
    {
        $user = auth()->user();
        if (!$user) abort(403);

        $isAdmin = method_exists($user,'hasRole') ? $user->hasRole('admin') : ($user->is_admin ?? false);
        $isOwner = Schema::hasColumn('documents', 'user_id') && $document->user_id
            ? ((int)$document->user_id === (int)$user->id)
            : false;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'شما امکان انجام این عملیات را ندارید.');
        }
    }


    protected function restrictDocumentsByCategory(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $builder) use ($user) {
            $builder->where(function (Builder $general) {
                $general->whereNull('purchase_order_id')
                    ->whereNull('opportunity_id');
            });

            if ($this->canViewDocumentCategory($user, 'opportunity')) {
                $builder->orWhereNotNull('opportunity_id');
            }

            if ($this->canViewDocumentCategory($user, 'purchase')) {
                $builder->orWhereNotNull('purchase_order_id');
            }
        });
    }

    protected function canViewDocumentCategory(User $user, string $category): bool
    {
        $map = [
            'opportunity' => 'opportunity_documents.view',
            'purchase' => 'purchase_documents.view',
        ];

        $permission = $map[$category] ?? null;
        if (!$permission) {
            return true;
        }

        return $user->can($permission);
    }


}
