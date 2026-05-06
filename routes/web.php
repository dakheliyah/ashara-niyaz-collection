<?php

use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Auth\OneLoginHandoffController;
use Illuminate\Support\Facades\Route;

Route::get('/receipts/{uuid}', [DonationController::class, 'generateReceiptByUuid'])->name('receipt.public');

Route::get('/auth/onlgn-handoff', OneLoginHandoffController::class)->name('auth.onlgn-handoff');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
