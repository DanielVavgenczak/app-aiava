<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'App Ava AiLumnus') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/fai.jpeg') }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/tours/tour.js'])
</head>

<body class="bg-base-100/50 dark:bg-slate-900">
    <x-mary-toast />
    <div class="flex flex-col bg-red-300">
        <livewire:layout.header-top-app />
        <x-aside-bar />
        <main class="relative w-10/12 dark:bg-slate-700 top-20 left-[16.5%]  bg-gray-100 min-h-screen ">
            <div class="px-3">
                {{ $slot }}
            </div>
        </main>
        <button class="fixed z-40 bg-pink-600 right-5 bottom-5 btn-circle" x-data @click="$store.tourguide.initTour()"
            id="ativation-mai" x-show="!$store.tourguide.openMai">
            <x-mary-icon name="o-question-mark-circle" class="w-8 text-white" />
        </button>
    </div>
</body>

</html>
