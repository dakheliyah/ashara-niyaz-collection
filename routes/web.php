<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DonationController;



Route::get('/receipts/{uuid}', [DonationController::class, 'generateReceiptByUuid'])->name('receipt.public');

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
