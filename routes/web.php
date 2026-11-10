<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MmomLoginController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [MmomLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [MmomLoginController::class, 'login'])->name('login.post');
Route::post('/logout', [MmomLoginController::class, 'logout'])->name('logout');


require __DIR__.'/mmom_e2.php';

require __DIR__.'/mmom_e7.php';
