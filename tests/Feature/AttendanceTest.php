<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access attendance page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('academic.attendance'))
        ->assertOk()
        ->assertSeeLivewire('academic.attendance');
});

test('non-admin cannot access attendance page', function () {
    $user = User::factory()->create(['role' => 'siswa']);

    actingAs($user)
        ->get(route('academic.attendance'))
        ->assertForbidden();
});
