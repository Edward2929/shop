<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\BkashPaymentController;
use Modules\Payment\Http\Controllers\PayTRCallbackController;

Route::post('/bkash/get-token', [BkashPaymentController::class, 'getToken'])
    ->name('bkash.get_token');

Route::get('/bkash/create-payment', [BkashPaymentController::class, 'createPayment'])
    ->name('bkash.create_payment');

Route::post('/bkash/execute-payment', [BkashPaymentController::class, 'executePayment'])
    ->name('bkash.execute_payment');

Route::get('/bkash/query-payment', [BkashPaymentController::class, 'queryPayment'])
    ->name('bkash.query_payment');

Route::post('/paytr/callback', [PayTRCallbackController::class, 'handle'])
    ->name('paytr.callback');

Route::post('/paytr/bin-query', [PayTRCallbackController::class, 'binQuery'])
    ->name('paytr.bin_query');

Route::get('/paytr/installment-rates', [PayTRCallbackController::class, 'installmentRates'])
    ->name('paytr.installment_rates');
