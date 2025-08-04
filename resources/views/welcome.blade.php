<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ dark: (localStorage.getItem('dark') !== null)
                        ? localStorage.getItem('dark') === 'true'
                        : window.matchMedia('(prefers-color-scheme: dark)').matches }" x-init="$watch('dark', v => {
          localStorage.setItem('dark', v);
          document.documentElement.classList.toggle('dark', v);
      })">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Social Hub</title>

    <!-- Early set dark (no flash) -->
    <script>
    (() => {
        const s = localStorage.getItem('dark');
        const d = (s !== null) ? s === 'true' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (d) document.documentElement.classList.add('dark');
    })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Alpine -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
    <!-- Login/Register links -->
    @if (Route::has('login'))
    <div class="fixed top-0 right-0 z-50 p-6 text-right space-x-2">
        @auth
        <a href="{{ url('/dashboard') }}" class="text-sm font-medium underline hover:no-underline">Dashboard</a>
        @else
        <a href="{{ route('login') }}" class="text-sm font-medium underline hover:no-underline">Log in</a>
        @if (Route::has('register'))
        <a href="{{ route('register') }}" class="inline-flex items-center px-3 py-1.5 rounded border text-sm
                                  border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800">
            Register
        </a>
        @endif
        @endauth
    </div>
    @endif

    <!-- Dark mode toggle -->
    <div class="fixed top-4 left-4 z-50">
        <button @click="dark = !dark" type="button" class="p-2 rounded-md bg-gray-200 hover:bg-gray-300
                           dark:bg-gray-700 dark:hover:bg-gray-600">
            <span x-show="!dark">🌞</span>
            <span x-show="dark">🌙</span>
            <span class="sr-only">Cambiar tema</span>
        </button>
    </div>

    <!-- Main content -->
    <div class="relative min-h-screen flex items-center justify-center">
        <div class="max-w-6xl w-full mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <!-- Texto -->
                <div class="space-y-5">
                    <h1 class="text-3xl md:text-4xl font-semibold tracking-tight">
                        Bienvenido a <span class="text-gray-900 dark:text-white">Social Hub</span>
                    </h1>
                    <p class="text-sm md:text-base text-gray-600 dark:text-gray-300 leading-relaxed">
                        Gestiona y programa publicaciones en múltiples redes sociales desde un solo lugar.
                        Conecta tus cuentas, define horarios y controla tu cola de publicaciones fácilmente.
                    </p>

                    <ul class="space-y-2 text-sm md:text-base">
                        <li class="flex items-start gap-3">
                            <span class="mt-2 w-2 h-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                            Conexión segura con OAuth a tus redes sociales.
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-2 w-2 h-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                            Horarios personalizados para publicar automáticamente.
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-2 w-2 h-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                            Cola de publicaciones con historial y pendientes.
                        </li>
                    </ul>

                    <div class="flex gap-3 pt-2">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md
                                      bg-gray-900 text-white hover:bg-black
                                      dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                            Empezar ahora
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-md
                                      border border-gray-300 text-gray-700 hover:bg-gray-50
                                      dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            Ya tengo cuenta
                        </a>
                    </div>
                </div>

                <!-- Ilustración -->
                <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="aspect-[5/4] grid place-items-center bg-gray-50 dark:bg-gray-800">
                        <svg viewBox="0 0 300 200" class="w-full h-full p-8 text-gray-900 dark:text-gray-100"
                            fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="10" y="10" width="280" height="180" rx="16" class="fill-current opacity-5" />
                            <g stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="70" cy="60" r="14" />
                                <circle cx="230" cy="60" r="14" />
                                <circle cx="70" cy="140" r="14" />
                                <circle cx="230" cy="140" r="14" />
                                <path d="M84 60h132" />
                                <path d="M70 74v52" />
                                <path d="M230 74v52" />
                                <text x="150" y="110" text-anchor="middle" font-size="44" class="fill-current">SH</text>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>