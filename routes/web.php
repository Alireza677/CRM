<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketingController;
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
use App\Http\Controllers\SalesContactsController;
use App\Http\Controllers\SalesOrganizationsController;
use App\Http\Controllers\ProformaInvoiceController;
use App\Http\Controllers\Sales\OpportunityController;
use App\Http\Controllers\Sales\ContactController;
use App\Http\Controllers\Sales\ProformaController;
use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\PurchaseOrderController;
use App\Http\Controllers\PrintTemplateController;
use App\Http\Controllers\FormController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Marketing
    Route::get('/marketing', [MarketingController::class, 'index'])->name('marketing');
    
    // Sales Leads
    Route::resource('marketing/leads', SalesLeadController::class)->names('marketing.leads');



    
    // Sales Routes
    Route::get('/sales', [SalesController::class, 'index'])->name('sales');
    Route::resource('sales/opportunities', OpportunityController::class)->names('sales.opportunities');
    Route::get('/sales/contacts', [SalesContactsController::class, 'index'])->name('sales.contacts.index');
    Route::get('/sales/organizations', [SalesOrganizationsController::class, 'index'])->name('sales.organizations.index');
    Route::get('/sales/proforma-invoice', [ProformaInvoiceController::class, 'index'])->name('sales.proforma.index');
    Route::get('/sales/proformas', [ProformaController::class, 'index'])->name('sales.proformas.index');

    // Other sections
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory');
    Route::get('/support', [SupportController::class, 'index'])->name('support');
    Route::get('/projects', [ProjectsController::class, 'index'])->name('projects');
    Route::get('/tools', [ToolsController::class, 'index'])->name('tools');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/customize', [CustomizeController::class, 'index'])->name('customize');

    // Customers
    Route::resource('customers', CustomerController::class);

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::resource('products', ProductController::class);
        Route::resource('suppliers', SupplierController::class);
        Route::resource('purchase-orders', PurchaseOrderController::class);
    });

    Route::resource('print-templates', PrintTemplateController::class);

    Route::resource('forms', FormController::class)->names([
        'index' => 'forms.index',
        'create' => 'forms.create',
        'store' => 'forms.store',
        'show' => 'forms.show',
        'edit' => 'forms.edit',
        'update' => 'forms.update',
        'destroy' => 'forms.destroy'
    ]);

    Route::prefix('sales')->name('sales.')->group(function () {
        // ... existing sales routes ...

        // Quotation routes
        Route::resource('quotations', \App\Http\Controllers\Sales\QuotationController::class);
        Route::resource('proformas', \App\Http\Controllers\Sales\ProformaController::class);
    });
});

// Profile management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

use App\Http\Controllers\Sales\OpportunityController as SalesOpportunityController;


Route::prefix('sales')->name('sales.')->group(function () {
    Route::resource('opportunities', SalesOpportunityController::class)->names([
        'index' => 'opportunities.index',
        'create' => 'opportunities.create',
        'store' => 'opportunities.store',
        'show' => 'opportunities.show',
        'edit' => 'opportunities.edit',
        'update' => 'opportunities.update',
        'destroy' => 'opportunities.destroy'
    ]);
    
    // Contacts routes
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    
    // Organizations routes
    Route::get('/organizations', [App\Http\Controllers\Sales\OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [App\Http\Controllers\Sales\OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [App\Http\Controllers\Sales\OrganizationController::class, 'store'])->name('organizations.store');
});
