<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use App\Http\Controllers\User\AccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->prefix('portfolio')->group(function () {

    Route::post('/invest', [App\Http\Controllers\User\PotrfolioController::class, 'invest'])
    ->name('assets.portfolio.create');

    Route::get('/porfolio', [App\Http\Controllers\User\PotrfolioController::class, 'get'])
        ->name('assets.portfolio.get');
});
