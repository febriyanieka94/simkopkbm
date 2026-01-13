<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('students.index')" :current="request()->routeIs('students.index')" wire:navigate>
                        {{ __('Siswa') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="briefcase" :href="route('ptk.index')" :current="request()->routeIs('ptk.index')" wire:navigate>
                        {{ __('Manajemen PTK') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group :heading="__('Akademik')" class="grid">
                    <flux:sidebar.item icon="calendar" :href="route('academic.years')" :current="request()->routeIs('academic.years')" wire:navigate>
                        {{ __('Tahun Ajaran') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="academic-cap" :href="route('academic.levels')" :current="request()->routeIs('academic.levels')" wire:navigate>
                        {{ __('Jenjang') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office" :href="route('academic.classrooms')" :current="request()->routeIs('academic.classrooms')" wire:navigate>
                        {{ __('Kelas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="book-open" :href="route('academic.subjects')" :current="request()->routeIs('academic.subjects')" wire:navigate>
                        {{ __('Mata Pelajaran') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-group" :href="route('academic.assignments')" :current="request()->routeIs('academic.assignments')" wire:navigate>
                        {{ __('Penugasan Guru') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="check-badge" :href="route('academic.attendance')" :current="request()->routeIs('academic.attendance')" wire:navigate>
                        {{ __('Presensi') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="pencil-square" :href="route('academic.grades')" :current="request()->routeIs('academic.grades')" wire:navigate>
                        {{ __('Penilaian') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group label="{{ __('Keuangan') }}" class="mt-6">
                    <flux:sidebar.item icon="wallet" :href="route('financial.payments')" :current="request()->routeIs('financial.payments')" wire:navigate>
                        {{ __('Transaksi') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('financial.billings')" :current="request()->routeIs('financial.billings')" wire:navigate>
                        {{ __('Tagihan') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="swatch" :href="route('financial.categories')" :current="request()->routeIs('financial.categories')" wire:navigate>
                        {{ __('Kategori Biaya') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group label="{{ __('Analitik') }}" class="mt-6">
                    <flux:sidebar.item icon="chart-bar" :href="route('reports')" :current="request()->routeIs('reports')" wire:navigate>
                        {{ __('Laporan') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav> --}}

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>


        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
