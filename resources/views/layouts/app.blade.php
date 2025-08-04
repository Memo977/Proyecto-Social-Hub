<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{ dark: localStorage.getItem('dark') === 'true', open: false }"
    x-init="$watch('dark', val => localStorage.setItem('dark', val))" x-bind:class="dark ? 'dark' : ''">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- AlpineJS (Breeze usa Alpine para el menú responsive; lo usamos también para dark mode) -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('dashboard') }}">
                                <x-application-logo class="block h-9 w-auto text-gray-800 dark:text-gray-200" />
                            </a>
                        </div>

                        <!-- Nav links (ejemplo) -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium
                                   border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300
                                   dark:text-gray-300 dark:hover:text-white dark:hover:border-gray-500">
                                Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Acciones derecha -->
                    <div class="flex items-center">
                        <!-- Toggle Dark/Light -->
                        <button @click="dark = !dark" type="button" class="mr-3 p-2 rounded-md bg-gray-200 hover:bg-gray-300
                                           dark:bg-gray-700 dark:hover:bg-gray-600" x-tooltip.raw="Cambiar tema">
                            <span x-show="!dark" aria-hidden="true">🌞</span>
                            <span x-show="dark" aria-hidden="true">🌙</span>
                            <span class="sr-only">Cambiar tema</span>
                        </button>

                        <!-- User dropdown / auth -->
                        <div class="hidden sm:flex sm:items-center sm:ml-6">
                            @auth
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none transition ease-in-out duration-150">
                                    Cerrar sesión
                                </button>
                            </form>
                            @endauth
                        </div>

                        <!-- Hamburger -->
                        <div class="-mr-2 flex items-center sm:hidden">
                            <button @click="open = !open"
                                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100
                                               dark:hover:bg-gray-700 focus:outline-none transition duration-150 ease-in-out">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden"
                                        stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsive Menu -->
            <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <a href="{{ route('dashboard') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium
                                  text-gray-600 hover:text-gray-800 hover:bg-gray-50
                                  dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700">
                        Dashboard
                    </a>
                    @auth
                    <form method="POST" action="{{ route('logout') }}" class="pl-3 pr-4 py-2">
                        @csrf
                        <button type="submit"
                            class="text-left w-full text-gray-600 hover:text-gray-800 hover:bg-gray-50
                                               dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 rounded-md px-2 py-1">
                            Cerrar sesión
                        </button>
                    </form>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Contenido -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>