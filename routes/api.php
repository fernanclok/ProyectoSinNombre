<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\PropertyController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\RentalApplicationController;
use App\Http\Controllers\AppointmentController;
use App\Models\Appoinment;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentHistoryController;
use App\Models\Rental_application;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// contracts
Route::prefix('contracts')->group(function () {
    Route::get('/', [ContractController::class, 'index']);
    Route::get('/{id}', [ContractController::class, 'getContract']);
    Route::post('/create', [ContractController::class, 'store']);
    Route::put('/terminate/{id}', [ContractController::class, 'terminateContract']);
    Route::post('/{id}/renew', [ContractController::class, 'renewContract']);
    Route::get('/get/user_tenant', [ContractController::class, 'getTenantUsers']);
    Route::get('/get/properties', [ContractController::class, 'getProperties']);
    Route::get('/tenant/{id}', [ContractController::class, 'getTenantContract']);
});

// zones
Route::get('/zones', [ZoneController::class, 'getZones']);

//Comments
Route::prefix('comments')->group(function () {
    Route::get('/{userId}', [PropertyController::class, 'getComments']);
    Route::post('/', [PropertyController::class, 'createComment']);
});

// properties
Route::prefix('properties')->group(function () {
    Route::get('/', [PropertyController::class, 'get']);
    Route::post('/create', [PropertyController::class, 'create']);
    Route::get('/filter', [PropertyController::class, 'getFilteredProperties']);
    Route::get('/getProperties', [PropertyController::class, 'getProperties']);
    Route::get('/featuredProperties', [PropertyController::class, 'featuredProperties']);

    Route::get('/getPropertyDetails/{id}', [PropertyController::class, 'getPropertyDetails']);
    Route::post('/appointment', [AppointmentController::class, 'createAppoinment']);
    Route::get('/applications', [PropertyController::class, 'getAllApplications']);
    Route::post('/applicate', [PropertyController::class, 'createApplication']);
    Route::post('/document-application', [RentalApplicationController::class, 'storeAppDocuments']);

    Route::post('/pass-documents', [RentalApplicationController::class, 'passDocuments']);
    Route::post('/pass-user-documents', [RentalApplicationController::class, 'updateUserDocuments']);
    Route::post('/user-applications', [RentalApplicationController::class, 'applicationsMadeByUser']);

    Route::get('/file/{filePath}', [RentalApplicationController::class, 'showUserFile']);
});

Route::get('/properties/{id}', [PropertyController::class, 'show']);
Route::put('/properties/{id}', [PropertyController::class, 'update']);
Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);

// appointments
Route::prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'getUserAppointments']);
    Route::get('/requests', [AppointmentController::class, 'getOwnerRequests']);
    Route::put('/update', [AppointmentController::class, 'updateAppointment']);
});

// dashboard
Route::get('/payment-history/{tenantUserId}', [DashboardController::class, 'getPaymentHistory']);
Route::get('/rented-property/{tenantUserId}', [DashboardController::class, 'getRentedProperty']);
Route::get('/tenant/contracts/{tenantUserId}', [DashboardController::class, 'getTenantContracts']);

//notifications
Route::get('/notifications/{userId}', [DashboardController::class, 'getNotifications']);
Route::put('/notifications/{id}/read', [DashboardController::class, 'markAsRead']);
Route::put('/notifications/{id}/unread', [DashboardController::class, 'markAsUnread']);
Route::post('/notifications', [DashboardController::class, 'sendNotification']);


// rental application
Route::prefix('rental-applications')->group(function () {
    Route::get('/', [RentalApplicationController::class, 'index']);
    Route::post('/{id}/approve', [RentalApplicationController::class, 'approve']);
    Route::post('/{id}/reject', [RentalApplicationController::class, 'reject']);
});

//Maintenace
Route::prefix('maintenance')->group(function () {
    Route::get('/', [MaintenanceController::class, 'index']);
    Route::get('/maintenancesReqOwner', [MaintenanceController::class, 'getRequestsByProperty']);
    Route::get('/getPropertyByTenant', [MaintenanceController::class, 'getPropertyByTenant']);
    Route::post('/store', [MaintenanceController::class, 'store']);
    Route::patch('/{id}',[MaintenanceController::class, 'update']);
    Route::put('/maintenancesReqOwner/{id}', [MaintenanceController::class, 'updateRequest']);
});

//MaintenaceOwner
Route::prefix('maintenanceOwner')->group(function () {
    Route::get('/properties', [MaintenanceController::class, 'getProperties']);
    Route::get('/maintenancesReq', [MaintenanceController::class, 'getRequestsByProperty']);
    Route::put('/maintenancesReq/{id}', [MaintenanceController::class, 'updateRequest']);
});

// Invoices
Route::prefix('Invoices')->group(function () {
    Route::get('/tenatn-invoices', [InvoiceController::class, 'MyInvoices']);
    Route::get('/invoices/{id}/pdf', [InvoiceController::class, 'generatePDF']);
    Route::post('/invoices/{id}/update-evidence', [InvoiceController::class, 'updateEvidence']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::patch('/invoices/{id}/invoice-paid', [InvoiceController::class, 'InvoicePaid']);
});
Route::post('/contracts/{contractId}/generate-invoices', [InvoiceController::class, 'generateInvoices']);

//payment history
Route::prefix('payment-history')->group(function () {
    Route::get('/', [PaymentHistoryController::class, 'getPaymentHistoriesByOwner']);
    Route::get('/get/tenant', [PaymentHistoryController::class, 'getPaymentHistoriesByTenant']);
    Route::get('/get/tenants-history', [PaymentHistoryController::class, 'getTenantsforHistory']);
    Route::get('/get/unpaid-invoices', [PaymentHistoryController::class, 'getUnpaidInvoices']);
});
