<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            {{-- Sección de Autenticación en dos pasos --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Autenticación en dos pasos (2FA)') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                @if(auth()->user()->two_factor_enabled)
                                {{ __('Tu cuenta está protegida con autenticación de dos factores.') }}
                                @else
                                {{ __('Agrega una capa extra de seguridad a tu cuenta activando la autenticación de dos factores.') }}
                                @endif
                            </p>
                        </header>

                        <div class="mt-6 flex items-center justify-between">
                            <div class="flex items-center">
                                @if(auth()->user()->two_factor_enabled)
                                <div class="flex items-center text-green-600 dark:text-green-400">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-sm font-medium">{{ __('Activado') }}</span>
                                </div>
                                @else
                                <div class="flex items-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                        </path>
                                    </svg>
                                    <span class="text-sm font-medium">{{ __('Desactivado') }}</span>
                                </div>
                                @endif
                            </div>

                            <a href="{{ route('two-factor.show') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                @if(auth()->user()->two_factor_enabled)
                                {{ __('Administrar 2FA') }}
                                @else
                                {{ __('Configurar 2FA') }}
                                @endif
                            </a>
                        </div>
                    </section>
                </div>
            </div>

            {{-- Redes conectadas --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-2xl">
                    <section>
                        <header class="mb-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Redes conectadas</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Conecta o desconecta tus cuentas.
                            </p>
                        </header>

                        @php
                        $user = auth()->user();
                        $mastodon = $user->socialAccounts()->where('provider','mastodon')->first();
                        $reddit = $user->socialAccounts()->where('provider','reddit')->first();
                        $pill = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium';
                        @endphp

                        <div class="space-y-4">
                            {{-- Mastodon --}}
                            <div
                                class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center gap-3">
                                    <span class="group inline-flex h-10 w-10 items-center justify-center rounded-lg
             ring-1 transition-colors
             bg-[#6364FF]/10 ring-[#6364FF]/30 hover:bg-[#6364FF]/20
             dark:bg-[#8B8CFF]/10 dark:ring-[#8B8CFF]/30 dark:hover:bg-[#8B8CFF]/20">
                                        <img src="https://cdn.simpleicons.org/mastodon/6364FF/8B8CFF" alt="Mastodon"
                                            class="w-6 h-6 transition group-hover:brightness-110" width="24" height="24"
                                            loading="lazy">
                                    </span>

                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Mastodon
                                        </div>
                                        @php
                                        // Valores que puedas tener guardados
                                        $rawUser = $mastodon->username ?? $mastodon->name ?? null;
                                        $rawInst = $mastodon->instance_domain
                                        ?? ($mastodon->domain ?? ($mastodon->meta['domain'] ?? null));

                                        // Normaliza user y dominio
                                        $user = $rawUser ? ltrim($rawUser, '@') : null;

                                        $host = $rawInst ?: null;
                                        if ($host) {
                                        // si viene con https://… toma solo el host
                                        if (preg_match('/^https?:\/\//i', $host)) {
                                        $host = parse_url($host, PHP_URL_HOST) ?: $host;
                                        }
                                        $host = ltrim($host, '@/');
                                        $host = rtrim($host, '/');
                                        }

                                        // Construye la URL y etiqueta a mostrar
                                        $mUrl = ($user && $host) ? 'https://'.$host.'/@'.$user : null; //
                                        $mLabel = ($user && $host) ? $host.'/@'.$user : ($user ? '@'.$user : 'usuario');
                                        @endphp

                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            @if($mastodon)
                                            Conectado como
                                            @if($mUrl)
                                            <a href="{{ $mUrl }}" target="_blank" rel="noopener"
                                                class="font-medium text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ $mLabel }}
                                            </a>
                                            @else
                                            <span class="font-medium">{{ $mLabel }}</span>
                                            @endif
                                            @else
                                            No conectado
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($mastodon)
                                    <span
                                        class="{{ $pill }} bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-400/30">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Conectado
                                    </span>
                                    <form method="POST" action="{{ route('social.disconnect', 'mastodon') }}">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-2 rounded-lg text-xs font-medium bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                                            Desconectar
                                        </button>
                                    </form>
                                    @else
                                    <a href="{{ route('oauth.mastodon.redirect') }}"
                                        class="px-3 py-2 rounded-lg text-xs font-medium bg-indigo-600 text-white hover:bg-indigo-700">Conectar</a>
                                    @endif
                                </div>
                            </div>

                            {{-- Reddit --}}
                            <div
                                class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center gap-3">
                                    <span class="group inline-flex h-10 w-10 items-center justify-center rounded-lg
             ring-1 transition-colors
             bg-[#FF4500]/10 ring-[#FF4500]/30 hover:bg-[#FF4500]/20
             dark:bg-[#FF7A50]/10 dark:ring-[#FF7A50]/30 dark:hover:bg-[#FF7A50]/20">
                                        <img src="https://cdn.simpleicons.org/reddit/FF4500/FF7A50" alt="Reddit"
                                            class="w-6 h-6 transition group-hover:brightness-110" width="24" height="24"
                                            loading="lazy">
                                    </span>

                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">Reddit</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            @if($reddit)
                                            Conectado como <span
                                                class="font-medium">u/{{ $reddit->username ?? $reddit->name ?? 'usuario' }}</span>
                                            @else
                                            No conectado
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($reddit)
                                    <span
                                        class="{{ $pill }} bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-400/30">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Conectado
                                    </span>
                                    <form method="POST" action="{{ route('social.disconnect', 'reddit') }}">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-2 rounded-lg text-xs font-medium bg-gray-200 text-gray-800 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                                            Desconectar
                                        </button>
                                    </form>
                                    @else
                                    <a href="{{ route('oauth.reddit.redirect') }}"
                                        class="px-3 py-2 rounded-lg text-xs font-medium bg-orange-600 text-white hover:bg-orange-700">Conectar</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>


            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>