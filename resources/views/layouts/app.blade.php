<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/tourboard.js'])
</head>

<body class="bg-base-100/50 dark:bg-slate-900">
    <x-mary-toast />
    <div class="flex flex-col">
        <livewire:layout.header-top-app />
        <div class="flex w-full bg-gray-200 dark:bg-slate-700">
            <x-aside-bar />
            <main class="w-full h-screen">
                {{ $slot }}
            </main>
        </div>
        <div x-data>
            <!-- Overlay tourguide -->
            <div x-show="$store.tourguide.openMai" class="fixed inset-0 z-40 bg-black bg-opacity-50 backdrop-blur-sm">
                <img src="{{ asset('images/ai-sos.png') }}" class="absolute right-0 w-64 bottom-4 ">
                <x-mary-button label="Fechar" class="fixed py-2 bg-pink-600 top-4 right-4"
                    x-show="$store.tourguide.openMai" @click="$store.tourguide.closeTour()" />
            </div>
            <button class="fixed z-40 bg-pink-600 right-5 bottom-5 btn-circle" x-data
                @click="$store.tourguide.toggleTourguide()" id="ativation-mai" x-show="!$store.tourguide.openMai">
                <x-mary-icon name="o-question-mark-circle" class="w-8 text-white" />
            </button>
        </div>
    </div>
</body>

</html>
