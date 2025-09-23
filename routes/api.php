<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\InvoiceController;

Route::apiResource('invoices', InvoiceController::class);
