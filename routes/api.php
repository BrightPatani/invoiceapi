<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//the route below automatically creates all required endpoints listed. 
Route::prefix('v1')->group(function () {
    Route::apiResource('invoices', InvoiceController::class);
});