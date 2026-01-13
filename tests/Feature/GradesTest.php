<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access grades page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.grades'))
        ->assertOk()
        ->assertSeeLivewire('academic.grades');
});

test('non-admin cannot access grades page', function () {
    $user = User::factory()->create(['role' => 'siswa']);

    actingAs($user)
        ->get(route('academic.grades'))
        ->assertForbidden();
});
