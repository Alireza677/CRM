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

        // تأیید نهایی (کنترلر تخصصی)
        Route::post('proformas/{proforma}/approve', [ProformaController::class, 'approve'])
            ->name('proformas.approve');

        // نقل‌قول‌ها
        Route::resource('quotations', QuotationController::class);

        // داشبورد فروش
        Route::get('/', [SalesController::class, 'index'])->name('index');

        // نسخه قدیمی (در صورت نیاز)
        Route::get('proforma-invoice', [ProformaInvoiceController::class, 'index'])->name('proforma.index');
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

    Route::middleware(['auth','role:Admin'])
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
    
        // Tasks: ایجاد (می‌تواند بیرون از گروهِ can:view,project باشد، اما ما می‌بریم زیرش تا یکدست باشد)
        Route::prefix('projects/{project}')
            ->name('projects.')
            ->middleware('can:view,project') // کاربر باید عضو پروژه باشد
            ->group(function () {
    
                // Create task
                Route::post('tasks',                     [TaskController::class, 'store'])->name('tasks.store');
    
                // Task CRUD
                Route::get('tasks/{task}',              [TaskController::class, 'show'])->name('tasks.show');
                Route::get('tasks/{task}/edit',         [TaskController::class, 'edit'])->name('tasks.edit');
                Route::put('tasks/{task}',              [TaskController::class, 'update'])->name('tasks.update');
                Route::delete('tasks/{task}',           [TaskController::class, 'destroy'])->name('tasks.destroy');
    
                // Mark done
                Route::post('tasks/{task}/done',        [TaskController::class, 'markDone'])->name('tasks.done');
    
                // Task notes
                Route::post('tasks/{task}/notes',                     [TaskNoteController::class, 'store'])->name('tasks.notes.store');
                Route::delete('tasks/{task}/notes/{note}',            [TaskNoteController::class, 'destroy'])->name('tasks.notes.destroy');
            });
    });
    

    

});

require __DIR__.'/auth.php';

