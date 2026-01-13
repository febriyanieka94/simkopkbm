<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access students page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('students.index'))
        ->assertOk()
        ->assertSeeLivewire('students.index');
});

test('non-admin cannot access students page', function () {
    $user = User::factory()->create(['role' => 'siswa']);

    actingAs($user)
        ->get(route('students.index'))
        ->assertForbidden();
});
