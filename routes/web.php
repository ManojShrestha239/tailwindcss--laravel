<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/calendar', [App\Http\Controllers\DashboardController::class, 'calendar'])->name('calendar');
    Route::get('/profile', [App\Http\Controllers\DashboardController::class, 'profile'])->name('profile');
    Route::get('/baseTable', [App\Http\Controllers\DashboardController::class, 'baseTable'])->name('baseTable');
    Route::get('/404', [App\Http\Controllers\DashboardController::class, 'notfound'])->name('404');
    Route::get('/maintenance', [App\Http\Controllers\DashboardController::class, 'maintenance'])->name('maintenance');

    // Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
