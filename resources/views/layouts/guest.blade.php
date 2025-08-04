<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: localStorage.getItem('dark') === 'true' }"
    x-init="$watch('dark', val => localStorage.setItem('dark', val))" x-bind:class="dark ? 'dark' : ''">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- AlpineJS para el toggle -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <!-- Botón de tema arriba a la derecha -->
    <div class="absolute top-4 right-4">
        <button @click="dark = !dark" type="button" class="p-2 rounded-md bg-gray-200 hover:bg-gray-300
                           dark:bg-gray-700 dark:hover:bg-gray-600">
            <span x-show="!dark" aria-hidden="true">🌞</span>
            <span x-show="dark" aria-hidden="true">🌙</span>
            <span class="sr-only">Cambiar tema</span>
        </button>
    </div>

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div>
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-gray-300" />
            </a>
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg dark:bg-gray-800">

            {{-- Mensajes de estado (ej. después de reset password) --}}
            @if (session('status'))
            <div class="mb-4 text-sm text-green-600 dark:text-green-400">
                {{ session('status') }}
            </div>
            @endif

            {{-- Errores de validación / estado auth --}}
            <x-auth-session-status class="mb-4" :status="session('status')" />
            <x-auth-validation-errors class="mb-4" :errors="$errors" />

            {{-- Contenido dinámico (login, register, etc.) --}}
            {{ $slot }}
        </div>
    </div>
</body>

</html>