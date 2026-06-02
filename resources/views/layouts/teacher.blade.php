<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <!-- Garanta que tenha APENAS esta tag viewport -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>IAva - Painel do professor</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/fai.jpeg') }}" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Catamaran:wght@100..900&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
      <x-mary-toast />
    <div class="flex flex-col bg-gray-50 min-w-min" x-data>
         <livewire:teacher.layout.header />
         <livewire:teacher.layout.menu />
        <main
            class="relative h-screen p-2 transition-all duration-300 ease-in-out top-[100px]"
            x-bind:class="$store.expandedSidebar.expanded ? 'w-[calc(100%-16.666667%)] left-[16.666667%]' :
                'w-[calc(100%-5rem)] left-20'">
            {{ $slot }}
        </main>
        <x-mary-button
            class="fixed z-[999] bg-blue-200 right-5 bottom-5 btn-circle btn-lg text-gray-50"
            x-on:click="$store.tourguide.initTour()"
            x-show="!$store.tourguide.openMai">
            <img
                src="{{ asset('images/rb-aiava.png') }}"
                alt="Iniciar tour guiado"
                class="w-[100px] h-auto"
            />
        </x-mary-button>
    </div>
</body>
</html>
