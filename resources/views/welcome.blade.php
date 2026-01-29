<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>App Ailumnus</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

    <link rel="icon" type="image/jpeg" href="{{ asset('images/fai.jpeg') }}" />
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
    <div class="flex flex-col items-center justify-center w-full h-screen gap-4">
        <img src="{{ asset('images/logo.png') }}" alt="Logo plaraforma de ensino ailumnus" class="w-48 " />
        <div class="">

            @if (Route::has('teacher.login'))
                <livewire:welcome.navigation />
            @endif
        </div>
    </div>
</body>

</html>
