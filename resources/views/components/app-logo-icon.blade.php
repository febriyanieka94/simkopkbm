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
    <div {{ $attributes->merge(['class' => 'bg-primary flex items-center justify-center text-white text-xl font-bold rounded-lg']) }}>
        {{ substr(config('app.name'), 0, 1) }}
    </div>
@endif

