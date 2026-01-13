<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('/students', 'students.index')->name('students.index');
});
