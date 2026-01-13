@php
    $logoExists = file_exists(public_path('img/logo.png')) || file_exists(public_path('img/logo.svg'));
@endphp

@if($logoExists)
    @php
        $extension = file_exists(public_path('img/logo.svg')) ? 'svg' : 'png';
    @endphp
    <img src="{{ asset('img/logo.' . $extension) }}" {{ $attributes->except(['class'])->merge(['class' => $attributes->get('class')]) }}>
@else
    {{-- Placeholder jika file belum ada --}}
<<<<<<< HEAD
    <div {{ $attributes->merge(['class' => 'bg-primary flex items-center justify-center text-white text-xl font-bold rounded-lg']) }}>
=======
    <div {{ $attributes->merge(['class' => 'bg-primary flex items-center justify-center text-white font-bold rounded']) }}>
>>>>>>> 0127cd91b4c5aa59913f405ffd3af2ecd76f270c
        {{ substr(config('app.name'), 0, 1) }}
    </div>
@endif

