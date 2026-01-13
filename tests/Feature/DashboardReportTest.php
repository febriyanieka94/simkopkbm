<?php

use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutVite;

beforeEach(fn () => withoutVite());

test('admin can access dashboard with livewire component', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeLivewire('dashboard');
});

test('admin can access reports page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('reports'))
        ->assertOk()
        ->assertSeeLivewire('reports');
});

test('reports page shows financial tab by default', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('reports'))
        ->assertSee('Laporan Keuangan')
        ->assertSee('Total Pendapatan');
});
