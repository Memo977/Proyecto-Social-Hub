<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>

            <a href="{{ route('posts.create') }}" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium
                  bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring
                  focus:ring-blue-500/50 dark:bg-blue-500 dark:hover:bg-blue-600">
                Nueva publicación
            </a>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('posts.queue') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium
              bg-gray-200 text-gray-800 hover:bg-gray-300
              dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                Pendientes
            </a>
            <a href="{{ route('posts.history') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium
              bg-gray-200 text-gray-800 hover:bg-gray-300
              dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                Histórico
            </a>
            <a href="{{ route('posts.create') }}" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium
              bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring
              focus:ring-blue-500/50 dark:bg-blue-500 dark:hover:bg-blue-600">
                Nueva publicación
            </a>
        </div>

    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("You're logged in!") }}
                </div>
                <a href="{{ route('oauth.mastodon.redirect') }}"
                    class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                    Conectar Mastodon
                </a>

                {{-- ✅ link con estilos de Breeze --}}
                <a href="{{ route('oauth.reddit.redirect') }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    Conectar Reddit
                </a>

                <a href="{{ route('schedules.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded">
                    Mis Horarios
                </a>

            </div>
        </div>
    </div>
</x-app-layout>