<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('academic.teacher-assignments');

    $component->assertSee('');
});
