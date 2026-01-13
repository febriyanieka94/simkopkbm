<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/academic.php';
require __DIR__.'/students.php';
require __DIR__.'/financial.php';
require __DIR__.'/ptk.php';


