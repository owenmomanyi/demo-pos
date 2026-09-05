<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoicePdfController;

Route::get('/', function () {
    return view('welcome');
});

Route::get(
    '/invoices/{invoice}/pdf',
    [InvoicePdfController::class, 'download']
)->name('invoices.pdf');