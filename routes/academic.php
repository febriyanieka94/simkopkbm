<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Volt::route('/academic/years', 'academic.academic-years')->name('academic.years');
    Volt::route('/academic/levels', 'academic.levels')->name('academic.levels');
    Volt::route('/academic/classrooms', 'academic.classrooms')->name('academic.classrooms');
    Volt::route('/academic/subjects', 'academic.subjects')->name('academic.subjects');
    Volt::route('/academic/assignments', 'academic.teacher-assignments')->name('academic.assignments');
    Volt::route('/academic/attendance', 'academic.attendance')->name('academic.attendance');
    Volt::route('/academic/grades', 'academic.grades')->name('academic.grades');
});
