<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::middleware('auth')->group(function () {

    Route::get('/', [TransactionController::class, 'index'])->name('home');

    Route::get('/deposit', [TransactionController::class, 'get_all_deposit'])->name('deposits.get');
    Route::post('/deposit', [TransactionController::class, 'store_deposit'])->name('deposits.store');

    Route::get('/withdrawal', [TransactionController::class, 'get_all_withdrawal'])->name('withdrawals.get');
    Route::post('/withdrawal', [TransactionController::class, 'store_withdrawal'])->name('withdrawals.store');
});
