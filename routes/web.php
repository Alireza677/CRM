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
    Route::get('/sales/opportunities', [OpportunityController::class, 'index'])->name('sales.opportunities.index');
    Route::get('/sales/opportunities/create', [OpportunityController::class, 'create'])->name('sales.opportunities.create');
    Route::post('/sales/opportunities', [OpportunityController::class, 'store'])->name('sales.opportunities.store');
    Route::get('/sales/contacts', [SalesContactsController::class, 'index'])->name('sales.contacts.index');
    Route::get('/sales/organizations', [SalesOrganizationsController::class, 'index'])->name('sales.organizations.index');
    Route::get('/sales/proforma-invoice', [ProformaInvoiceController::class, 'index'])->name('sales.proforma.index');

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

    // New route for sales.opportunities
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::resource('opportunities', OpportunityController::class);
    });
});

// Profile management
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
