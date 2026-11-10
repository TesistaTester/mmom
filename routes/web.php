<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


require __DIR__.'/mmom_e2.php';

require __DIR__.'/mmom_e7.php';
