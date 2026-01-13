@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-7 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-10 items-center justify-center rounded-lg bg-accent-content text-accent-foreground">
            <x-app-logo-icon class="size-7 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
