<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <!-- Garanta que tenha APENAS esta tag viewport -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Area do Estudante') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/fai.jpeg') }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Catamaran:wght@100..900&display=swap" rel="stylesheet">

    <!-- Scripts -->

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-base-100/50 dark:bg-slate-900">
    <x-mary-toast />
    <div class="flex flex-col bg-gray-100 min-w-min" x-data>
        <livewire:pages.student.components.header />
        <livewire:pages.student.components.aside />
        <main class="relative h-full p-2 transition-all duration-300 ease-in-out top-16 "
            x-bind:class="$store.expandedSidebar.expanded ? 'w-[calc(100%-16.666667%)] left-[16.666667%]' :
                'w-[calc(100%-5rem)] left-20'">
            {{ $slot }}

        </main>
        <x-mary-button class="fixed z-[999] bg-pink-600 right-5 bottom-5 btn-circle btn-lg text-gray-50"
            x-on:click="$store.tourguide.initTour()" id="ativation-mai" icon="o-question-mark-circle"
            x-show="!$store.tourguide.openMai" />
    </div>
</body>

</html>
