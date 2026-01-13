<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('ptk.index');

    $component->assertSee('');
});
