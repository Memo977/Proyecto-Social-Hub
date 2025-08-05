<x-guest-layout>
    <div class="max-w-md mx-auto mt-10">
        {{-- Alert de error --}}
        @if ($errors->any())
        <div
            class="mb-4 rounded border border-red-300/30 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-red-800 dark:text-red-200">
            {{ $errors->first() }}
        </div>
        @endif

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="px-6 pt-6">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('Verificación en dos pasos') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Ingresa el código de tu autenticador o un recovery code.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="px-6 pb-6 pt-4 space-y-4">
                @csrf

                {{-- Código TOTP --}}
                <div>
                    <x-input-label for="code" :value="__('Código del autenticador (6 dígitos)')" />
                    <x-text-input id="code" name="code" inputmode="numeric" pattern="[0-9]*" class="mt-1 block w-full"
                        autocomplete="one-time-code" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div class="relative my-1">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-gray-200 dark:border-gray-700"></span>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white dark:bg-gray-800 px-2 text-xs uppercase tracking-wider text-gray-400">
                            {{ __('o') }}
                        </span>
                    </div>
                </div>

                {{-- Recovery code --}}
                <div>
                    <x-input-label for="recovery_code" :value="__('Recovery code')" />
                    <x-text-input id="recovery_code" name="recovery_code" class="mt-1 block w-full"
                        autocomplete="off" />
                    <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
                </div>

                <div class="pt-2">
                    <x-primary-button class="w-full justify-center">
                        {{ __('Validar') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>