<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access financial categories', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('financial.categories'))
        ->assertOk()
        ->assertSeeLivewire('financial.categories');
});

test('admin can access financial billings', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('financial.billings'))
        ->assertOk()
        ->assertSeeLivewire('financial.billings');
});

test('admin can access financial payments', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('financial.payments'))
        ->assertOk()
        ->assertSeeLivewire('financial.payments');
});

test('non-admin cannot access financial routes', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    actingAs($user)
        ->get(route('financial.categories'))
        ->assertForbidden();
})->with(['siswa', 'guru', 'staf']);
