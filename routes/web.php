<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\Marketing\LeadController;
use App\Http\Controllers\Marketing\LeadNoteController;

use App\Http\Controllers\SalesController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CustomizeController;
use App\Http\Controllers\SalesLeadController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\Sales\ContactImportController;
use App\Http\Controllers\Sales\OpportunityController;
use App\Http\Controllers\Sales\ContactController;
use App\Http\Controllers\Sales\ProformaController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\OrganizationController;
use App\Http\Controllers\Sales\DocumentController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\PrintTemplateController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\WorkflowController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\OpportunityNoteController;
use App\Http\Controllers\Settings\AutomationController;
use App\Http\Controllers\Sales\OrganizationImportController;
use App\Http\Controllers\Sales\ProformaImportController;
use App\Http\Controllers\Sales\ProformaApprovalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskNoteController;
use App\Http\Controllers\Inventory\ProductImportController;
use App\Http\Controllers\CalendarController;




Route::get('/', function () {
    return view('welcome');
});

// ------------------------------
// Protected Routes (auth)
// ------------------------------
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            
    
    Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing');
            // ریسورس اصلی که نمایش، ویرایش، حذف، و ایجاد سرنخ‌ها را پوشش می‌دهد
    Route::resource('marketing/leads', SalesLeadController::class)->names('marketing.leads');

            // حذف گروهی از کنترلری که خودت ویرایشش کردی
    Route::post('/marketing/leads/bulk-delete', [SalesLeadController::class, 'bulkDelete'])->name('marketing.leads.bulk-delete');
            
            // سایر عملیات مربوط به تب‌ها و نوت‌ها
    Route::prefix('marketing/leads')->group(function () {
        Route::get('{lead}/tab/{tab}', [LeadController::class, 'loadTab'])->name('marketing.leads.tab');
        Route::post('{lead}/notes', [LeadNoteController::class, 'store'])->name('marketing.leads.notes.store');
    });

    
    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('read/{notification}', [NotificationController::class, 'read'])->name('read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::post('bulk-action', [NotificationController::class, 'bulkAction'])->name('bulkAction');
    });
    
    //// Sales
    Route::prefix('sales')->name('sales.')->group(function () {
        // فرصت‌های فروش
        Route::resource('opportunities', OpportunityController::class)->names('opportunities');
        Route::get('opportunities/{opportunity}/tab/{tab}', [OpportunityController::class, 'loadTab'])->name('opportunities.tab');
        Route::post('opportunities/{opportunity}/notes', [OpportunityNoteController::class, 'store'])->name('opportunities.notes.store');

        // سرنخ‌ها
        Route::resource('leads', SalesLeadController::class)->names('leads');

        // اسناد
        Route::resource('documents', DocumentController::class);
        Route::get('documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        // مخاطبین
        Route::get('contacts/import', [ContactImportController::class, 'showForm'])->name('contacts.import.form');
        Route::post('contacts/import', [ContactImportController::class, 'import'])->name('contacts.import');
        Route::delete('contacts/bulk-delete', [ContactController::class, 'bulkDelete'])->name('contacts.bulk_delete');
        Route::resource('contacts', ContactController::class);

        // سازمان‌ها
        Route::get('organizations/import', [OrganizationImportController::class, 'importForm'])->name('organizations.import.form');
        Route::post('organizations/import', [OrganizationImportController::class, 'import'])->name('organizations.import');
        Route::delete('organizations/bulk-delete', [OrganizationController::class, 'bulkDelete'])->name('organizations.bulkDelete'); // قبل از resource
        Route::resource('organizations', OrganizationController::class)->names('organizations');

        // پیش‌فاکتور
        Route::get('proformas/import', [ProformaImportController::class, 'Form'])->name('proformas.import.form');
        Route::post('proformas/import', [ProformaImportController::class, 'import'])->name('proformas.import');
        Route::delete('proformas/bulk-delete', [ProformaController::class, 'bulkDestroy'])->name('proformas.bulk-destroy');
        Route::resource('proformas', ProformaController::class);
        Route::post('proformas/{proforma}/items', [ProformaController::class, 'storeItems'])->name('proformas.items.store');
        Route::post('proformas/{proforma}/send-for-approval', [ProformaController::class, 'sendForApproval'])->name('proformas.sendForApproval');

        // تصمیم‌گیری مرحله‌ای: approve | reject
        Route::post('proformas/{proforma}/approvals/{step}/{decision}', [ProformaApprovalController::class, 'decide'])
        ->whereNumber('step')
        ->whereIn('decision', ['approve','reject'])
        ->name('proformas.approvals.decide');
        Route::post('proformas/{proforma}/reject', [ProformaController::class, 'reject'])
        ->name('proformas.reject');

        // روت قدیمی برای تأیید نهایی (در صورت استفاده جاهای دیگر)
        Route::post('proformas/{proforma}/approve', [ProformaController::class, 'approve'])
            ->name('proformas.approve');

        // نقل‌قول‌ها
        Route::resource('quotations', QuotationController::class);

        // داشبورد فروش
        Route::get('/', [SalesController::class, 'index'])->name('index');

        // نسخه قدیمی (در صورت نیاز)
        Route::get('proforma-invoice', [ProformaInvoiceController::class, 'index'])->name('proforma.index');
    });

    
    Route::prefix('inventory')->name('inventory.')->group(function () {
        // --- Import routes (قبل از resource ها) ---
        Route::get('products/import', [ProductImportController::class, 'create'])->name('products.import');
        Route::post('products/import/dry-run', [ProductImportController::class, 'dryRun'])->name('products.import.dryrun');
        Route::post('products/import/confirm', [ProductImportController::class, 'store'])->name('products.import.store');
    
        // --- Resources ---
        Route::resource('products', ProductController::class)->except(['show'])->whereNumber('product');
        Route::resource('suppliers', SupplierController::class);          // => inventory.suppliers.index
        Route::resource('purchase-orders', PurchaseOrderController::class);
    });

    // Other Modules
    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents');
    Route::get('/customize', [CustomizeController::class, 'index'])->name('customize');

    // approval
    Route::get('/approvals/pending', [\App\Http\Controllers\ApprovalController::class, 'pending'])
    ->middleware('auth')
    ->name('approvals.pending');
    
    Route::post('/approvals/{approval}/action', [\App\Http\Controllers\ApprovalController::class, 'handleAction'])
    ->middleware('auth')
    ->name('approvals.action');

    


    Route::post('settings/users/reassign', [UserController::class, 'reassign'])->name('settings.users.reassign');


    // Customers
    Route::resource('customers', CustomerController::class);

    // Print Templates
    Route::resource('print-templates', PrintTemplateController::class);

    // Forms
    Route::resource('forms', FormController::class)->names([
        'index' => 'forms.index',
        'create' => 'forms.create',
        'store' => 'forms.store',
        'show' => 'forms.show',
        'edit' => 'forms.edit',
        'update' => 'forms.update',
        'destroy' => 'forms.destroy',
    ]);

    Route::middleware(['auth','role:admin'])
    ->prefix('settings')->name('settings.')
    ->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
        Route::get('/automation', [AutomationController::class, 'edit'])->name('automation.edit');
        Route::post('/automation/update', [AutomationController::class, 'update'])->name('automation.update');
        Route::delete('/automation/delete-all', [AutomationController::class, 'destroyAll'])->name('automation.destroyAll');
    });


    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Global search
    Route::get('/global-search', [GlobalSearchController::class, 'index'])->name('global.search');

    Route::scopeBindings()->group(function () {

        // Projects
        Route::get('/projects',               [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/create',        [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects',              [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}',     [ProjectController::class, 'show'])->name('projects.show');
    
        // Members
        Route::post('/projects/{project}/members',          [ProjectController::class, 'addMember'])->name('projects.members.add');
        Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'removeMember'])->name('projects.members.remove');
    
        Route::prefix('projects/{project}')
    ->name('projects.')
    ->middleware('can:view,project')  // کاربر باید عضو پروژه باشد
    ->scopeBindings()                 // بایندینگ تو در تو: task متعلق به project و note متعلق به task
    ->group(function () {

        /*
        |-------------------------
        | Tasks (CRUD + Done)
        |-------------------------
        */

        // ایجاد تسک
        Route::post('tasks', [TaskController::class, 'store'])
            ->name('tasks.store')
            ->whereNumber('project');

        // نمایش تسک (با یادداشت‌ها) => به TaskNoteController منتقل شد
        Route::get('tasks/{task}', [TaskNoteController::class, 'show'])
            ->name('tasks.show')
            ->whereNumber('project')
            ->whereNumber('task');

        // ویرایش/به‌روزرسانی/حذف توسط TaskController
        Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])
            ->middleware('can:update,task')
            ->name('tasks.edit')
            ->whereNumber('project')
            ->whereNumber('task');

        Route::put('tasks/{task}', [TaskController::class, 'update'])
            ->middleware('can:update,task')
            ->name('tasks.update')
            ->whereNumber('project')
            ->whereNumber('task');

        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
            ->middleware('can:delete,task')
            ->name('tasks.destroy')
            ->whereNumber('project')
            ->whereNumber('task');

        // علامت زدن به‌عنوان انجام‌شده
        Route::post('tasks/{task}/done', [TaskController::class, 'markDone'])
            ->middleware('can:update,task')
            ->name('tasks.done')
            ->whereNumber('project')
            ->whereNumber('task');

        /*
        |-------------------------
        | Task Notes (Store/Destroy)
        |-------------------------
        */

        // ثبت یادداشتِ جدید برای تسک
        Route::post('tasks/{task}/notes', [TaskNoteController::class, 'store'])
            ->middleware('can:view,task') // یا can:create, App\Models\Note اگر پالیسی جدا دارید
            ->name('tasks.notes.store')
            ->whereNumber('project')
            ->whereNumber('task');

        // حذف یادداشت
        Route::delete('tasks/{task}/notes/{note}', [TaskNoteController::class, 'destroy'])
            ->middleware('can:delete,note')
            ->name('tasks.notes.destroy')
            ->whereNumber('project')
            ->whereNumber('task')
            ->whereNumber('note');
    });
    });
    
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');


    

});

require __DIR__.'/auth.php';

