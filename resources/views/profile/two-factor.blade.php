<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100">
            {{ __('Autenticación en dos pasos (2FA)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @if (session('status'))
                <div
                    class="rounded-lg border border-green-300/30 bg-green-50 dark:bg-green-900/30 px-6 py-4 text-green-800 dark:text-green-200 shadow-sm">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-green-600 dark:text-green-400" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                        {{ session('status') }}
                    </div>
                </div>
                @endif

                {{-- Cuando aún no hay secreto generado --}}
                @if (! auth()->user()->two_factor_secret)
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('Activar 2FA') }}</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Confirma tu contraseña para generar el secreto y el código QR.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('two-factor.enable') }}" class="px-8 py-6">
                        @csrf
                        <div class="max-w-md">
                            <x-input-label for="password" :value="__('Contraseña')" />
                            <x-text-input id="password" name="password" type="password" class="mt-2 block w-full"
                                required />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="mt-6">
                            <x-primary-button class="px-6 py-3">
                                {{ __('Generar secreto y QR') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Si ya hay secreto pero aún no está confirmado (mostrar QR + confirmar) --}}
                @if ($otpauth && ! auth()->user()->two_factor_enabled)
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('Escanea el QR en Google Authenticator') }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Si no puedes escanear, usa el secreto manual') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6">
                        <div class="grid md:grid-cols-2 gap-8">
                            {{-- QR Code Section --}}
                            <div class="flex flex-col items-center">
                                <div
                                    class="inline-block rounded-xl bg-white p-4 shadow-lg border dark:bg-gray-900 dark:border-gray-600">
                                    {!! QrCode::size(200)->generate($otpauth) !!}
                                </div>
                                <div class="mt-4 text-center">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ __('Secreto manual:') }}
                                    </p>
                                    <code
                                        class="inline-block rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm text-gray-800 dark:text-gray-200 font-mono break-all">{{ $secret }}</code>
                                </div>
                            </div>

                            {{-- Confirmation Form --}}
                            <div>
                                <form method="POST" action="{{ route('two-factor.confirm') }}">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="code" :value="__('Código de 6 dígitos')" />
                                            <x-text-input id="code" name="code" inputmode="numeric" pattern="[0-9]*"
                                                class="mt-2 block w-full text-center text-lg tracking-widest"
                                                placeholder="000000" maxlength="6" required />
                                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                                        </div>

                                        <div class="pt-4">
                                            <x-primary-button class="w-full justify-center px-6 py-3">
                                                {{ __('Confirmar y activar') }}
                                            </x-primary-button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Mostrar recovery codes recién generados (solo una vez) --}}
                @if (session('two_factor_plain_codes'))
                <div
                    class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 overflow-hidden shadow-xl sm:rounded-lg border border-yellow-200 dark:border-yellow-700">
                    <div class="px-8 py-6 border-b border-yellow-200 dark:border-yellow-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                    </path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('Recovery Codes') }}</h3>
                                <p class="mt-1 text-sm text-yellow-700 dark:text-yellow-300 font-medium">
                                    {{ __('¡IMPORTANTE! Guárdalos en un lugar seguro. Cada uno es de un solo uso.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6">
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach (session('two_factor_plain_codes') as $code)
                            <div class="relative">
                                <code
                                    class="block w-full rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 px-4 py-3 text-center text-sm font-mono text-gray-800 dark:text-gray-200 shadow-sm">
                                    {{ $code }}
                                </code>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-6 p-4 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <strong>💡 Consejo:</strong> Guarda estos códigos en un gestor de contraseñas o
                                imprímelos y guárdalos en un lugar seguro.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- 2FA activo: desactivar o regenerar códigos --}}
                @if (auth()->user()->two_factor_enabled)
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-8 py-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ __('2FA Activo') }}</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {{ __('Tu cuenta está protegida con autenticación de dos factores.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6">
                        <div class="grid gap-6 lg:grid-cols-2">
                            {{-- Regenerar recovery codes --}}
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-6">
                                <div class="flex items-center mb-4">
                                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400 mr-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Regenerar Códigos
                                    </h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Genera nuevos códigos de recuperación. Los anteriores dejarán de funcionar.
                                </p>

                                <form method="POST" action="{{ route('two-factor.recovery') }}">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="password_recovery" :value="__('Contraseña')" />
                                            <x-text-input id="password_recovery" name="password" type="password"
                                                class="mt-2 block w-full" required />
                                        </div>
                                        <x-primary-button
                                            class="w-full justify-center bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700">
                                            {{ __('Regenerar Recovery Codes') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>

                            {{-- Desactivar 2FA --}}
                            <div
                                class="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                                <div class="flex items-center mb-4">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400 mr-3" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                        </path>
                                    </svg>
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100">Zona Peligrosa</h4>
                                </div>
                                <p class="text-sm text-red-700 dark:text-red-300 mb-4">
                                    Desactivar 2FA reducirá la seguridad de tu cuenta significativamente.
                                </p>

                                <form method="POST" action="{{ route('two-factor.disable') }}">
                                    @csrf @method('DELETE')
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="password_disable" :value="__('Contraseña')" />
                                            <x-text-input id="password_disable" name="password" type="password"
                                                class="mt-2 block w-full" required />
                                        </div>
                                        <x-danger-button class="w-full justify-center">
                                            {{ __('Desactivar 2FA') }}
                                        </x-danger-button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>