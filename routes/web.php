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
use App\Http\Controllers\Marketing\LeadFavoriteController;
use App\Http\Controllers\Marketing\LeadNoteController;

use App\Http\Controllers\SalesController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Support\AfterSalesServiceController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CustomizeController;
use App\Http\Controllers\SalesLeadController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\Sales\ContactImportController;
use App\Http\Controllers\Sales\OpportunityController;
use App\Http\Controllers\Sales\OpportunityImportController;
use App\Http\Controllers\Sales\ContactController;
use App\Http\Controllers\Sales\ProformaController;
use App\Http\Controllers\Sales\QuotationController;
use App\Http\Controllers\Sales\OrganizationController;
use App\Http\Controllers\Sales\AjaxCreateController;
use App\Http\Controllers\Sales\DocumentController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\PrintTemplateController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\WorkflowController;
use App\Http\Controllers\Settings\NotificationRuleController;
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
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\Marketing\LeadExportController;
use App\Services\Sms\FarazEdgeService;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\RoleReportController;
use App\Http\Controllers\Admin\RolePermissionMatrixController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\EmployeePortalController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-faraz-sms', function (FarazEdgeService $sms) {
    // Ø´Ù…Ø§Ø±Ù‡â€ŒÙ‡Ø§ Ø¨Ø§ÛŒØ¯ E.164 Ø¨Ø§Ø´Ù†Ø¯: +98912XXXXXXX
    return $sms->sendWebservice(
        ['+98912XXXXXXX'],                    // ÛŒÚ© ÛŒØ§ Ú†Ù†Ø¯ Ú¯ÛŒØ±Ù†Ø¯Ù‡
        'Ø³Ù„Ø§Ù…! ØªØ³Øª Ø§Ø±Ø³Ø§Ù„ Ø§Ø² CRM âœ…',           // Ù…ØªÙ† Ù¾ÛŒØ§Ù…
        null,                                 // Ø§Ø² Ø´Ù…Ø§Ø±Ù‡ Ù¾ÛŒØ´â€ŒÙØ±Ø¶ .env
        null                                  // ÛŒØ§ Ù…Ø«Ù„Ø§Ù‹ '2025-03-12 21:20:02' (UTC)
    );
});
// Temporary debug route to verify inline image streaming
Route::get('/debug/test-image', function () {
    $path = public_path('test.png');
    abort_unless(file_exists($path), 404, 'test.png not found in public/.');

    $stream = fopen($path, 'rb');
    $size   = filesize($path) ?: null;
    $mime   = 'image/png';

    if (function_exists('ob_get_level') && ob_get_level()) {
        @ob_end_clean();
    }

    return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($stream) {
        fpassthru($stream);
        if (is_resource($stream)) fclose($stream);
    }, 200, [
        'Content-Type' => $mime,
        'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        'Content-Length' => is_numeric($size) ? (string) $size : null,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->name('debug.test-image');

// ------------------------------
// Protected Routes (auth)
// ------------------------------
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing');
    Route::get('marketing/leads/favorites', [LeadFavoriteController::class, 'index'])->name('marketing.leads.favorites.index');
    Route::post('marketing/leads/{lead}/favorite', [LeadFavoriteController::class, 'store'])->name('marketing.leads.favorites.store');
    Route::delete('marketing/leads/{lead}/favorite', [LeadFavoriteController::class, 'destroy'])->name('marketing.leads.favorites.destroy');
    // Ø±ÛŒØ³ÙˆØ±Ø³ Ø§ØµÙ„ÛŒ Ú©Ù‡ Ù†Ù…Ø§ÛŒØ´ØŒ ÙˆÛŒØ±Ø§ÛŒØ´ØŒ Ø­Ø°Ù Ùˆ Ø§ÛŒØ¬Ø§Ø¯ Ø³Ø±Ù†Ø®â€ŒÙ‡Ø§ Ø±Ø§ Ù¾ÙˆØ´Ø´ Ù…ÛŒâ€ŒØ¯Ù‡Ø¯
    Route::resource('marketing/leads', SalesLeadController::class)->names('marketing.leads');
    Route::post('marketing/leads/{lead}/convert', [SalesLeadController::class, 'convertToOpportunity'])
        ->name('marketing.leads.convert');

    // Ø­Ø°Ù Ú¯Ø±ÙˆÙ‡ÛŒ Ø§Ø² Ú©Ù†ØªØ±Ù„ÛŒ Ú©Ù‡ Ø®ÙˆØ¯Øª ÙˆÛŒØ±Ø§ÛŒØ´Ø´ Ú©Ø±Ø¯ÛŒ
    Route::post('/marketing/leads/bulk-delete', [SalesLeadController::class, 'bulkDelete'])->name('marketing.leads.bulk-delete');

    // Ø³Ø§ÛŒØ± Ø¹Ù…Ù„ÛŒØ§Øª Ù…Ø±Ø¨ÙˆØ· Ø¨Ù‡ ØªØ¨â€ŒÙ‡Ø§ Ùˆ Ù†ÙˆØªâ€ŒÙ‡Ø§
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
        // ÙØ±ØµØªâ€ŒÙ‡Ø§ÛŒ ÙØ±ÙˆØ´
        // Opportunities (with permission middleware)
        Route::get('opportunities/create', [OpportunityController::class, 'create'])
            ->name('opportunities.create')
            ->middleware('can:opportunities.create');
        Route::post('opportunities', [OpportunityController::class, 'store'])
            ->name('opportunities.store')
            ->middleware('can:opportunities.create');
        Route::get('opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])
            ->name('opportunities.edit')
            ->middleware('can:update,opportunity');
        Route::put('opportunities/{opportunity}', [OpportunityController::class, 'update'])
            ->name('opportunities.update')
            ->middleware('can:update,opportunity');
        // Bulk delete opportunities (placed before parameter route to avoid conflicts)
        Route::delete('opportunities/bulk-delete', [OpportunityController::class, 'bulkDelete'])
            ->name('opportunities.bulk_delete');
        Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroy'])
            ->whereNumber('opportunity')
            ->name('opportunities.destroy')
            ->middleware('can:delete,opportunity');
        // Import (Dry run + Confirm)
        Route::get('opportunities/import', [OpportunityImportController::class, 'create'])->name('opportunities.import');
        Route::post('opportunities/import/dry-run', [OpportunityImportController::class, 'dryRun'])->name('opportunities.import.dryrun');
        Route::post('opportunities/import/confirm', [OpportunityImportController::class, 'store'])->name('opportunities.import.store');

        Route::resource('opportunities', OpportunityController::class)->names('opportunities')
            ->except(['create','store','edit','update','destroy']);
        Route::get('opportunities/{opportunity}/tab/{tab}', [OpportunityController::class, 'loadTab'])->name('opportunities.tab');
        Route::post('opportunities/{opportunity}/notes', [OpportunityNoteController::class, 'store'])->name('opportunities.notes.store');

        // Ø³Ø±Ù†Ø®â€ŒÙ‡Ø§
        Route::get('leads/export', [LeadExportController::class, 'export'])
            ->name('leads.export');
        // Ø§Ú©Ø³Ù¾ÙˆØ±Øª Ø¨Ø§ ÙØ±Ù…Øª Ø¯Ø± Ù…Ø³ÛŒØ±: /sales/leads/export/xlsx
        Route::get('leads/export/{format}', [LeadExportController::class, 'export'])
            ->whereIn('format', ['csv', 'xlsx'])
            ->name('leads.export.format');
        // Ø³Ø±Ù†Ø®â€ŒÙ‡Ø§ (Ù…ÙˆØ¬ÙˆØ¯)
        Route::get('leads/create', [SalesLeadController::class, 'create'])
            ->name('leads.create')
            ->middleware('can:leads.create');
        Route::post('leads', [SalesLeadController::class, 'store'])
            ->name('leads.store')
            ->middleware('can:leads.create');
        Route::get('leads/{lead}/edit', [SalesLeadController::class, 'edit'])
            ->name('leads.edit')
            ->middleware('can:update,lead');
        Route::put('leads/{lead}', [SalesLeadController::class, 'update'])
            ->name('leads.update')
            ->middleware('can:update,lead');
        Route::delete('leads/{lead}', [SalesLeadController::class, 'destroy'])
            ->name('leads.destroy')
            ->middleware('can:delete,lead');
        Route::resource('leads', SalesLeadController::class)->names('leads')
            ->except(['create','store','edit','update','destroy']);

        // Ø§Ø³Ù†Ø§Ø¯
        // Custom index to show two separate paginated sections (opportunities, purchase orders)
        Route::get('documents', function () {
            // Enforce policy: deny if role has no documents.view.* permission
            abort_unless(\Illuminate\Support\Facades\Gate::allows('viewAny', \App\Models\Document::class), 403);

            $user = auth()->user();

            $opportunityDocs = \App\Models\Document::visibleFor($user, 'documents')
                ->with(['opportunity','user'])
                ->whereNotNull('opportunity_id')
                ->latest()
                ->paginate(20, ['*'], 'op_page');

            $purchaseOrderDocs = \App\Models\Document::visibleFor($user, 'documents')
                ->with(['purchaseOrder','user'])
                ->whereNotNull('purchase_order_id')
                ->latest()
                ->paginate(20, ['*'], 'po_page');

            $breadcrumb = [
                ['title' => 'Ø¯Ø§Ø´Ø¨ÙˆØ±Ø¯', 'url' => route('dashboard')],
                ['title' => 'Ø§Ø³Ù†Ø§Ø¯'],
            ];

            return view('sales.documents.index_split', compact('opportunityDocs','purchaseOrderDocs','breadcrumb'));
        })->name('documents.index');

        // Use custom index (two-column split); exclude index from resource
        Route::resource('documents', DocumentController::class)->except(['index']);
        Route::get('documents/{document}/view', [DocumentController::class, 'view'])->name('documents.view');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        // Ù…Ø®Ø§Ø·Ø¨ÛŒÙ†
        Route::get('contacts/import', [ContactImportController::class, 'showForm'])
            ->name('contacts.import.form');
        Route::post('contacts/import', [ContactImportController::class, 'import'])
            ->name('contacts.import');
        Route::get('contacts/export', [ContactImportController::class, 'export'])
            ->name('contacts.export');
        Route::get('contacts/export/{format}', [ContactImportController::class, 'export'])
            ->whereIn('format', ['csv', 'xlsx'])
            ->name('contacts.export.format');
        Route::delete('contacts/bulk-delete', [ContactController::class, 'bulkDelete'])
            ->name('contacts.bulk_delete');
        Route::get('contacts/{contact}/tab/{tab}', [ContactController::class, 'loadTab'])->name('contacts.tab');
        // AJAX lightweight create endpoints for inline modals
        Route::post('ajax/contacts', [AjaxCreateController::class, 'contact'])->name('ajax.contacts.store');
        Route::post('ajax/organizations', [AjaxCreateController::class, 'organization'])->name('ajax.organizations.store');

        Route::resource('contacts', ContactController::class);

        // Ø³Ø§Ø²Ù…Ø§Ù†â€ŒÙ‡Ø§
        Route::get('organizations/import', [OrganizationImportController::class, 'importForm'])->name('organizations.import.form');
        Route::post('organizations/import', [OrganizationImportController::class, 'import'])->name('organizations.import');
        Route::delete('organizations/bulk-delete', [OrganizationController::class, 'bulkDelete'])->name('organizations.bulkDelete'); // Ù‚Ø¨Ù„ Ø§Ø² resource
        Route::get('organizations/{organization}/tab/{tab}', [OrganizationController::class, 'loadTab'])->name('organizations.tab');
        Route::resource('organizations', OrganizationController::class)->names('organizations');

        // Ù¾ÛŒØ´â€ŒÙØ§Ú©ØªÙˆØ±
        Route::get('proformas/import', [ProformaImportController::class, 'Form'])->name('proformas.import.form');
        Route::post('proformas/import', [ProformaImportController::class, 'import'])->name('proformas.import');
        Route::delete('proformas/bulk-delete', [ProformaController::class, 'bulkDestroy'])->name('proformas.bulk-destroy');
        Route::resource('proformas', ProformaController::class);
        Route::get('proformas/{proforma}/preview', [ProformaController::class, 'preview'])->name('proformas.preview');
        Route::post('proformas/{proforma}/items', [ProformaController::class, 'storeItems'])->name('proformas.items.store');
        Route::post('proformas/{proforma}/send-for-approval', [ProformaController::class, 'sendForApproval'])->name('proformas.sendForApproval');

        // ØªØµÙ…ÛŒÙ…â€ŒÚ¯ÛŒØ±ÛŒ Ù…Ø±Ø­Ù„Ù‡â€ŒØ§ÛŒ: approve | reject
        Route::post('proformas/{proforma}/approvals/{step}/{decision}', [ProformaApprovalController::class, 'decide'])
            ->whereNumber('step')
            ->whereIn('decision', ['approve','reject'])
            ->name('proformas.approvals.decide');
        Route::post('proformas/{proforma}/reject', [ProformaController::class, 'reject'])
            ->name('proformas.reject');

        // Ø±ÙˆØª Ù‚Ø¯ÛŒÙ…ÛŒ Ø¨Ø±Ø§ÛŒ ØªØ£ÛŒÛŒØ¯ Ù†Ù‡Ø§ÛŒÛŒ (Ø¯Ø± ØµÙˆØ±Øª Ø§Ø³ØªÙØ§Ø¯Ù‡ Ø¬Ø§Ù‡Ø§ÛŒ Ø¯ÛŒÚ¯Ø±)
        Route::post('proformas/{proforma}/approve', [ProformaController::class, 'approve'])
            ->name('proformas.approve');

        // Ù†Ù‚Ù„â€ŒÙ‚ÙˆÙ„â€ŒÙ‡Ø§
        Route::resource('quotations', QuotationController::class);

        // Ø¯Ø§Ø´Ø¨ÙˆØ±Ø¯ ÙØ±ÙˆØ´
        Route::get('/', [SalesController::class, 'index'])->name('index');

        // Ù†Ø³Ø®Ù‡ Ù‚Ø¯ÛŒÙ…ÛŒ (Ø¯Ø± ØµÙˆØ±Øª Ù†ÛŒØ§Ø²)
        Route::get('proforma-invoice', [ProformaInvoiceController::class, 'index'])->name('proforma.index');
    });

    // Inventory: Purchase Orders status update for workflow
    Route::post('inventory/purchase-orders/{purchaseOrder}/status', [PurchaseOrderController::class, 'updateStatus'])
        ->name('inventory.purchase-orders.updateStatus')
        ->whereNumber('purchaseOrder');
    // Workflow actions: approve / reject current stage
    Route::post('inventory/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])
        ->name('inventory.purchase-orders.approve')
        ->whereNumber('purchaseOrder');
    Route::post('inventory/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])
        ->name('inventory.purchase-orders.reject')
        ->whereNumber('purchaseOrder');
    // After accounting approval: creator marks delivered to warehouse
    Route::post('inventory/purchase-orders/{purchaseOrder}/deliver-to-warehouse', [PurchaseOrderController::class, 'deliverToWarehouse'])
        ->name('inventory.purchase-orders.deliverToWarehouse')
        ->whereNumber('purchaseOrder');

    Route::prefix('inventory')->name('inventory.')->group(function () {
        // --- Import routes (Ù‚Ø¨Ù„ Ø§Ø² resource Ù‡Ø§) ---
        Route::get('products/import', [ProductImportController::class, 'create'])->name('products.import');
        Route::post('products/import/dry-run', [ProductImportController::class, 'dryRun'])->name('products.import.dryrun');
        Route::post('products/import/confirm', [ProductImportController::class, 'store'])->name('products.import.store');

        // Suppliers import
        Route::get('suppliers/import', [\App\Http\Controllers\Inventory\SupplierImportController::class, 'create'])->name('suppliers.import');
        Route::post('suppliers/import/dry-run', [\App\Http\Controllers\Inventory\SupplierImportController::class, 'dryRun'])->name('suppliers.import.dryrun');
        Route::post('suppliers/import/confirm', [\App\Http\Controllers\Inventory\SupplierImportController::class, 'store'])->name('suppliers.import.store');

        // Single product details page
        Route::get('products/{product}', [ProductController::class, 'show'])
            ->whereNumber('product')
            ->name('products.show');

        // --- Resources ---
        Route::resource('products', ProductController::class)->except(['show'])->whereNumber('product');
        Route::resource('suppliers', SupplierController::class);          // => inventory.suppliers.index
        // Tabs loader for purchase orders (info, documents, notes, updates)
          Route::get('purchase-orders/{purchaseOrder}/tab/{tab}', [PurchaseOrderController::class, 'loadTab'])
              ->whereIn('tab', ['info','items','documents','notes','updates'])
              ->name('purchase-orders.tab');

        // Notes on purchase orders
        Route::post('purchase-orders/{purchaseOrder}/notes', [\App\Http\Controllers\PurchaseOrderNoteController::class, 'store'])
            ->name('purchase-orders.notes.store');

        Route::delete('purchase-orders/bulk-destroy', [PurchaseOrderController::class, 'bulkDestroy'])
            ->name('purchase-orders.bulk-destroy');

        Route::resource('purchase-orders', PurchaseOrderController::class);
    });

    // Other Modules
    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::prefix('support')->name('support.')->group(function () {
        Route::delete('after-sales-services/bulk-delete', [AfterSalesServiceController::class, 'bulkDestroy'])
            ->name('after-sales-services.bulk-destroy');
        Route::resource('after-sales-services', AfterSalesServiceController::class)->names('after-sales-services');
    });
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
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

    // Reports (auth + verified)
    Route::middleware(['auth','verified'])->group(function(){
        // Place specific routes before resource to avoid shadowing
        Route::get('reports/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');
        Route::put('reports/{report}/share', [ReportController::class, 'share'])->name('reports.share');
        Route::post('reports/preview', [ReportController::class, 'preview'])->name('reports.preview');
        Route::get('reports/{report}/run', [ReportController::class, 'run'])->name('reports.run');
        Route::get('reports/{report}/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
        Route::get('reports/{report}/export/xlsx', [ReportController::class, 'exportXlsx'])->name('reports.export.xlsx');
        Route::get('reports/{report}/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/{report}/schedules', [ReportController::class, 'schedules'])->name('reports.schedules');
        Route::post('reports/{report}/schedules', [ReportController::class, 'storeSchedule'])->name('reports.schedules.store');
        Route::delete('reports/{report}/schedules/{schedule}', [ReportController::class, 'destroySchedule'])->name('reports.schedules.destroy');
        Route::resource('reports', ReportController::class);
    });

    Route::middleware(['auth','role:admin'])
        ->prefix('settings')->name('settings.')
        ->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::get('/general', [SettingsController::class, 'general'])->name('general');
            Route::resource('users', UserController::class)->except(['show']);
            Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflows.index');
            Route::post('/workflows/purchase-orders', [WorkflowController::class, 'updatePurchaseOrder'])->name('workflows.purchase-orders.update');
            Route::get('/automation', [AutomationController::class, 'edit'])->name('automation.edit');
            Route::post('/automation/update', [AutomationController::class, 'update'])->name('automation.update');
            Route::delete('/automation/delete-all', [AutomationController::class, 'destroyAll'])->name('automation.destroyAll');

            // Notification Settings Matrix
            Route::get('/notifications', [NotificationRuleController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/preview', [NotificationRuleController::class, 'preview'])->name('notifications.preview');
            Route::post('/notifications', [NotificationRuleController::class, 'store'])->name('notifications.store');
            Route::put('/notifications/{notificationRule}', [NotificationRuleController::class, 'update'])->name('notifications.update');
            Route::patch('/notifications/{notificationRule}', [NotificationRuleController::class, 'update']);
            Route::delete('/notifications/{notificationRule}', [NotificationRuleController::class, 'destroy'])->name('notifications.destroy');
        });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Global search

    // Tools > SMS Sender
    Route::prefix('tools')->name('tools.')->group(function(){
        Route::get('sms', [SmsController::class, 'create'])->name('sms.create');
        Route::post('sms', [SmsController::class, 'send'])->name('sms.send');
        Route::get('sms/report', [SmsController::class, 'report'])->name('sms.report');
        Route::get('sms/report/export', [SmsController::class, 'exportCsv'])->name('sms.report.export');
        Route::post('sms/blacklist', [SmsController::class, 'addToBlacklist'])->name('sms.blacklist.add');

        // SMS Lists management
        Route::post('sms/lists', [SmsController::class, 'storeList'])->name('sms.lists.store');
        Route::delete('sms/lists/{list}', [SmsController::class, 'destroyList'])->name('sms.lists.destroy');
        Route::post('sms/lists/{list}/contacts', [SmsController::class, 'addContactsToList'])->name('sms.lists.contacts.add');
        Route::delete('sms/lists/{list}/contacts/{contact}', [SmsController::class, 'removeContactFromList'])->name('sms.lists.contacts.remove');
        Route::post('sms/lists/{list}/send', [SmsController::class, 'sendToList'])->name('sms.lists.send');
    });
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
            ->middleware('can:view,project')  // Ú©Ø§Ø±Ø¨Ø± Ø¨Ø§ÛŒØ¯ Ø¹Ø¶Ùˆ Ù¾Ø±ÙˆÚ˜Ù‡ Ø¨Ø§Ø´Ø¯
            ->scopeBindings()                 // Ø¨Ø§ÛŒÙ†Ø¯ÛŒÙ†Ú¯ ØªÙˆ Ø¯Ø± ØªÙˆ: task Ù…ØªØ¹Ù„Ù‚ Ø¨Ù‡ project Ùˆ note Ù…ØªØ¹Ù„Ù‚ Ø¨Ù‡ task
            ->group(function () {

                /*
                |-------------------------
                | Tasks (CRUD + Done)
                |-------------------------
                */

                // Ø§ÛŒØ¬Ø§Ø¯ ØªØ³Ú©
                Route::post('tasks', [TaskController::class, 'store'])
                    ->name('tasks.store')
                    ->whereNumber('project');

                // Ù†Ù…Ø§ÛŒØ´ ØªØ³Ú© (Ø¨Ø§ ÛŒØ§Ø¯Ø¯Ø§Ø´Øªâ€ŒÙ‡Ø§) => Ø¨Ù‡ TaskNoteController Ù…Ù†ØªÙ‚Ù„ Ø´Ø¯
                Route::get('tasks/{task}', [TaskNoteController::class, 'show'])
                    ->name('tasks.show')
                    ->whereNumber('project')
                    ->whereNumber('task');

                // ÙˆÛŒØ±Ø§ÛŒØ´/Ø¨Ù‡â€ŒØ±ÙˆØ²Ø±Ø³Ø§Ù†ÛŒ/Ø­Ø°Ù ØªÙˆØ³Ø· TaskController
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

                // Ø¹Ù„Ø§Ù…Øª Ø²Ø¯Ù† Ø¨Ù‡â€ŒØ¹Ù†ÙˆØ§Ù† Ø§Ù†Ø¬Ø§Ù…â€ŒØ´Ø¯Ù‡
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

                // Ø«Ø¨Øª ÛŒØ§Ø¯Ø¯Ø§Ø´Øª Ø¬Ø¯ÛŒØ¯ Ø¨Ø±Ø§ÛŒ ØªØ³Ú©
                Route::post('tasks/{task}/notes', [TaskNoteController::class, 'store'])
                    ->middleware('can:view,task') // ÛŒØ§ can:create, App\Models\Note Ø§Ú¯Ø± Ù¾Ø§Ù„ÛŒØ³ÛŒ Ø¬Ø¯Ø§ Ø¯Ø§Ø±ÛŒØ¯
                    ->name('tasks.notes.store')
                    ->whereNumber('project')
                    ->whereNumber('task');

                // Ø­Ø°Ù ÛŒØ§Ø¯Ø¯Ø§Ø´Øª
                Route::delete('tasks/{task}/notes/{note}', [TaskNoteController::class, 'destroy'])
                    ->middleware('can:delete,note')
                    ->name('tasks.notes.destroy')
                    ->whereNumber('project')
                    ->whereNumber('task')
                    ->whereNumber('note');
            });
    });

    // ------------------------------
    // Admin: Role/Permission Report
    // ------------------------------
    Route::prefix('admin')
        ->name('admin.')
        ->middleware(['auth', 'role:admin|admin_manager', 'can:reports.view'])
        ->group(function () {
            Route::get('role-permissions', [RoleReportController::class, 'index'])->name('role-permissions');
            Route::get('role-permissions/export/csv', [RoleReportController::class, 'exportCsv'])->name('role-permissions.csv');
            Route::get('role-permissions/export/markdown', [RoleReportController::class, 'exportMarkdown'])->name('role-permissions.markdown');
        });

    // Admin: Roles Permission Matrix (Editable)
    Route::middleware(['auth','role:admin'])->group(function () {
        Route::get('/roles/matrix', [RolePermissionMatrixController::class, 'index'])->name('roles.matrix');
        Route::post('/roles/matrix', [RolePermissionMatrixController::class, 'store'])->name('roles.matrix.store');
        Route::get('/roles/matrix/export', [RolePermissionMatrixController::class, 'exportJson'])->name('roles.matrix.export');
        Route::post('/roles/matrix/import', [RolePermissionMatrixController::class, 'importJson'])->name('roles.matrix.import');
    });

    Route::middleware(['auth'])->group(function () {
        Route::resource('activities', ActivityController::class);

        // Ø¯Ú©Ù…Ù‡ ØªØºÛŒÛŒØ± ÙˆØ¶Ø¹ÛŒØª Ø¨Ù‡ ØªÚ©Ù…ÛŒÙ„ Ø´Ø¯Ù‡
        Route::patch('activities/{activity}/complete', [\App\Http\Controllers\ActivityController::class, 'markComplete'])
            ->name('activities.complete');

        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::prefix('employee-portal')->name('employee.portal.')->group(function () {
            Route::get('/', [EmployeePortalController::class, 'index'])->name('index');
            Route::get('/contract', [EmployeePortalController::class, 'contract'])->name('contract');
            Route::get('/leave-request', [EmployeePortalController::class, 'leaveRequest'])->name('leave.request');
            Route::post('/leave-request', [EmployeePortalController::class, 'submitLeaveRequest'])->name('leave.submit');
            Route::get('/leaves', [EmployeePortalController::class, 'leaves'])->name('leaves');
            Route::get('/payslips', [EmployeePortalController::class, 'payslips'])->name('payslips');
            Route::get('/insurance', [EmployeePortalController::class, 'insurance'])->name('insurance');
        }); // ÙÛŒØ¯ Ø§ÛŒÙˆÙ†Øªâ€ŒÙ‡Ø§

        // Admin: Holidays management
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/admin/holidays', [HolidayController::class, 'index'])->name('holidays.index');
            Route::post('/admin/holidays', [HolidayController::class, 'store'])->name('holidays.store');
            Route::get('/admin/holidays/{holiday}/edit', [HolidayController::class, 'edit'])->name('holidays.edit');
            Route::put('/admin/holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
            Route::get('/admin/holidays/{holiday}', [HolidayController::class, 'show'])->name('holidays.show');
        });
    });

});

require __DIR__.'/auth.php';

