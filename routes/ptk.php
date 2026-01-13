<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('/ptk', 'ptk.index')->name('ptk.index');
});
