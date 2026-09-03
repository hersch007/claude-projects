<?php

use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Static pages
Route::view('/about', 'public.about')->name('about');
Route::view('/technology', 'public.technology')->name('technology');
Route::view('/knowledge', 'public.knowledge')->name('knowledge');
Route::view('/contact', 'public.contact')->name('contact');

// Eligibility checker — rate limited
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/check-eligibility', [EligibilityController::class, 'check'])->name('eligibility.check');
});

// Order flow
Route::get('/order/{product}', [OrderController::class, 'create'])->name('order.create');
Route::post('/order/{product}', [OrderController::class, 'store'])->name('order.store');
Route::get('/order/{payment}/complete', [OrderController::class, 'complete'])->name('order.complete');
Route::get('/order/{payment}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
