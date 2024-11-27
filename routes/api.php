<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\RentalApplicationController;
use App\Http\Controllers\AppoinmentController;
use App\Models\Appoinment;


use App\Http\Controllers\RentalApplicationController;


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
    Route::post('/create', [ContractController::class, 'store']);
    Route::get('/user_tenant', [ContractController::class, 'getTenantUsers']);
});

// zones
Route::get('/zones', [ZoneController::class, 'getZones']);

// properties
Route::prefix('properties')->group(function () {
    Route::get('/', [PropertyController::class, 'get']);
    Route::post('/create', [PropertyController::class, 'create']);
    Route::get('/filter', [PropertyController::class, 'getFilteredProperties']);
    Route::get('/getProperties', [PropertyController::class, 'getProperties']);

    Route::get('/getPropertyDetails/{id}', [PropertyController::class, 'getPropertyDetails']);
    Route::post('/appointment', [AppoinmentController::class, 'createAppoinment']);
    Route::get('/applications', [PropertyController::class, 'getAllApplications']);
    Route::post('/applicate', [PropertyController::class, 'createApplication']);
});

Route::prefix('rental-applications')->group(function(){
    Route::get('/', [RentalApplicationController::class, 'index']);
    Route::post('/{id}/approve', [RentalApplicationController::class, 'approve']);
    Route::post('/{id}/reject', [RentalApplicationController::class, 'reject']);
});
