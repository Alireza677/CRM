<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Opportunity;


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
        $documents = Document::with(['opportunity','user'])
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
        if ($request->filled('opportunity_id')) {
            $id = (int) $request->query('opportunity_id');
            if (Opportunity::whereKey($id)->exists()) {
                $defaultOpportunityId = $id;
            }
        }

        return view('sales.documents.create',
            compact('breadcrumb','opportunities','defaultOpportunityId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'          => ['required','string','max:255'],
            'file'           => ['required','file','max:10240','mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png'],
            'opportunity_id' => ['nullable','integer','exists:opportunities,id'],
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $data = [
            'title'          => $request->string('title'),
            'file_path'      => $path,
            'opportunity_id' => $request->integer('opportunity_id') ?: null,
        ];

        if (Schema::hasColumn('documents', 'user_id')) {
            $data['user_id'] = $request->user()->id;
        }

        $document = Document::create($data);

        // اگر از صفحه یک فرصت آمده‌ایم، برگرد همانجا (UX بهتر)
        if ($document->opportunity_id) {
            return redirect()
                ->route('sales.opportunities.show', $document->opportunity_id)
                ->with('success', 'سند برای این فرصت ثبت شد.');
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
        $this->authorizeDocument($document);

        if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'فایل یافت نشد.');
        }

        $mime = Storage::disk('public')->mimeType($document->file_path) ?? 'application/octet-stream';
        $contents = Storage::disk('public')->get($document->file_path);

        return response($contents, 200)->header('Content-Type', $mime);
    }

    /**
     * دانلود فایل
     */
    public function download(Document $document)
    {
        $this->authorizeDocument($document);

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
        $this->authorizeDocument($document);

        $breadcrumb = [
            ['title' => 'داشبورد', 'url' => route('dashboard')],
            ['title' => 'اسناد', 'url' => route('sales.documents.index')],
            ['title' => 'ویرایش سند'],
        ];

        $opportunities = \App\Models\Sales\Opportunity::select('id','title')->latest()->get();

        return view('sales.documents.edit', compact('document','breadcrumb','opportunities'));
    }

    /**
     * بروزرسانی سند (فایل اختیاری)
     */
    public function update(Request $request, Document $document)
    {
        $this->authorizeDocument($document);

        $request->validate([
            'title'           => ['required','string','max:255'],
            'file'            => ['nullable','file','max:10240', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png'],
            'opportunity_id'  => ['nullable','integer','exists:opportunities,id'],
        ]);

        $updates = [
            'title'          => $request->input('title'),
            'opportunity_id' => $request->input('opportunity_id'),
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
        $this->authorizeDocument($document);

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
    private function authorizeDocument(Document $document): void
    {
        // نمونه‌ی ساده: اگر ستون user_id هست، فقط مالک یا ادمین ببیند.
        if (Schema::hasColumn('documents', 'user_id') && $document->user_id) {
            $user = auth()->user();
            if (! $user) abort(403);

            $isOwner = (int)$document->user_id === (int)$user->id;
            $isAdmin = method_exists($user,'hasRole') ? $user->hasRole('admin') : $user->is_admin ?? false;

            if (! $isOwner && ! $isAdmin) {
                abort(403, 'شما امکان دسترسی ندارید.');
            }
        }
        // در غیر این صورت، اجازه بده (فقط auth middleware کفایت می‌کند)
    }
}
