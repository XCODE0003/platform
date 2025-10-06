<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('App/Home');
})->name('home');


require __DIR__.'/auth.php';
