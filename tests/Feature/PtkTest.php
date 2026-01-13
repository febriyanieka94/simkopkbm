<?php

use App\Models\User;
use App\Models\Profile;
use App\Models\TeacherProfile;
use App\Models\StaffProfile;
use Livewire\Volt\Volt;
use function Pest\Laravel\actingAs;

test('admin can access ptk management', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get(route('ptk.index'))
        ->assertOk()
        ->assertSeeLivewire('ptk.index');
});

test('can create teacher ptk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin);

    Volt::test('ptk.index')
        ->set('name', 'Guru Test')
        ->set('email', 'guru@test.com')
        ->set('password', 'password123')
        ->set('role', 'guru')
        ->set('nip', '12345')
        ->set('education_level', 'S1')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'guru@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('guru');
    expect($user->profile->profileable)->toBeInstanceOf(TeacherProfile::class);
});

test('can create staff ptk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin);

    Volt::test('ptk.index')
        ->set('name', 'Staff Test')
        ->set('email', 'staff@test.com')
        ->set('password', 'password123')
        ->set('role', 'staf')
        ->set('position', 'Administrasi')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'staff@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('staf');
    expect($user->profile->profileable)->toBeInstanceOf(StaffProfile::class);
    expect($user->profile->profileable->position)->toBe('Administrasi');
});

test('can delete ptk', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $ptk = User::factory()->create(['role' => 'guru']);
    $profileable = TeacherProfile::create(['nip' => '999']);
    Profile::create([
        'user_id' => $ptk->id,
        'profileable_id' => $profileable->id,
        'profileable_type' => TeacherProfile::class,
    ]);

    actingAs($admin);

    Volt::test('ptk.index')
        ->call('delete', $ptk->id);

    expect(User::find($ptk->id))->toBeNull();
    expect(TeacherProfile::find($profileable->id))->toBeNull();
});
