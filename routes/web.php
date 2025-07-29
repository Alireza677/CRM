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
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/read/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::post('/notifications/bulk-action', [NotificationController::class, 'bulkAction'])->name('notifications.bulkAction');

    //// Sales
    Route::prefix('sales')->name('sales.')->group(function () {
        // فرصت‌های فروش
        Route::resource('opportunities', OpportunityController::class)->names('opportunities');
        Route::resource('leads', SalesLeadController::class)->names('leads');

        // نمایش تب‌ها (مثل خلاصه، یادداشت، اطلاعات و ...)
        Route::get('opportunities/{opportunity}/tab/{tab}', [OpportunityController::class, 'loadTab'])->name('opportunities.tab');
        // ثبت یادداشت برای فرصت فروش
        Route::post('opportunities/{opportunity}/notes', [OpportunityNoteController::class, 'store'])
        ->name('opportunities.notes.store');


        //pishfaktor
        Route::resource('proformas', ProformaController::class); 
        Route::post('proformas/{proforma}/send-for-approval', [ProformaController::class, 'sendForApproval'])
                ->name('proformas.sendForApproval');
        Route::put('proformas/{proforma}/approve', [ProformaController::class, 'approve'])->name('proformas.approve');


        // اسناد
        Route::resource('documents', DocumentController::class);

        // مخاطبین
        Route::get('contacts/import', [ContactImportController::class, 'showForm'])->name('contacts.import.form');
        Route::post('contacts/import', [ContactImportController::class, 'import'])->name('contacts.import');
        Route::delete('contacts/bulk-delete', [ContactController::class, 'bulkDelete'])->name('contacts.bulk_delete');
        Route::resource('contacts', ContactController::class);

        

        // سازمان‌ها
        Route::resource('organizations', OrganizationController::class)->names('organizations');

        // پیش‌فاکتورها و پیش‌نویس‌ها
        Route::resource('proformas', ProformaController::class);
        Route::resource('quotations', QuotationController::class);
        Route::post('proformas/{proforma}/items', [ProformaController::class, 'storeItems'])->name('proformas.items.store');

        // صفحه اصلی فروش
        Route::get('/', [SalesController::class, 'index'])->name('index');

        // نسخه قدیمی پیش‌فاکتور (در صورت نیاز)
        Route::get('/proforma-invoice', [ProformaInvoiceController::class, 'index'])->name('proforma.index');
    });

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('purchase-orders', PurchaseOrderController::class);
    });

    // Other Modules
    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::get('/projects', [ProjectsController::class, 'index'])->name('projects');
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

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
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
});

require __DIR__.'/auth.php';

//مسیر دانلود سند
Route::get('/sales/documents/{document}/download', [DocumentController::class, 'download'])->name('sales.documents.download');
Route::get('/sales/documents/create', [DocumentController::class, 'create'])->name('sales.documents.create');
