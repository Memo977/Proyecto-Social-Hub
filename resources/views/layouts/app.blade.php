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

    <!-- Anti-FOUC: aplica el último tema guardado antes de renderizar -->
    <script>
    (function() {
        try {
            const theme = localStorage.getItem('theme'); // 'dark' | 'light' | null
            const color = localStorage.getItem('theme-color'); // p.ej. 'light','dark','corporate', etc.
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            if (color) document.documentElement.setAttribute('data-theme', color);
        } catch (_) {}
    })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        {{-- Barra de navegación estándar de Breeze --}}
        @include('layouts.navigation')

        {{-- Encabezado de página (opcional) --}}
        @isset($header)
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endisset

        {{-- Contenido --}}
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>