<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\User\AccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->prefix('assets')->group(function () {

    Route::post('/bill/create', [App\Http\Controllers\User\BillController::class, 'create'])
        ->name('assets.bill.create');

    Route::get('/bills', [App\Http\Controllers\User\BillController::class, 'get'])
        ->name('assets.bill.get');

    Route::post('/staking/start', [App\Http\Controllers\User\StakingController::class, 'start'])
        ->name('staking.start');

    Route::post('/staking/{id}/claim', [App\Http\Controllers\User\StakingController::class, 'claim'])
        ->name('staking.claim');
});
