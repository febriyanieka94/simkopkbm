<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access academic years page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.years'))
        ->assertOk()
        ->assertSeeLivewire('academic.academic-years');
});

test('admin can access levels page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.levels'))
        ->assertOk()
        ->assertSeeLivewire('academic.levels');
});

test('admin can access classrooms page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.classrooms'))
        ->assertOk()
        ->assertSeeLivewire('academic.classrooms');
});

test('admin can access subjects page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.subjects'))
        ->assertOk()
        ->assertSeeLivewire('academic.subjects');
});

test('admin can access assignments page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.assignments'))
        ->assertOk()
        ->assertSeeLivewire('academic.teacher-assignments');
});

test('non-admin cannot access academic routes', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    actingAs($user)
        ->get(route('academic.years'))
        ->assertForbidden();
})->with(['siswa', 'guru', 'staf']);
