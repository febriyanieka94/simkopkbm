<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('/financial/categories', 'financial.categories')->name('financial.categories');
    Volt::route('/financial/billings', 'financial.billings')->name('financial.billings');
    Volt::route('/financial/payments', 'financial.payments')->name('financial.payments');
    Volt::route('/reports', 'reports')->name('reports');
});
