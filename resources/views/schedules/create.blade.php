<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Nuevo horario
            </h2>
            <a href="{{ route('schedules.index') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                ← Volver
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @can('create', App\Models\PublicationSchedule::class)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden">
                <div class="p-6 sm:p-8">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Definir día y hora
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Elige un día de la semana y una hora exacta; puedes añadir varias horas para un mismo día.
                        </p>
                    </div>

                    <form action="{{ route('schedules.store') }}" method="POST"
                        x-data="scheduleForm({ day: {{ old('day_of_week', 0) }}, time: '{{ old('time') }}' })"
                        class="grid gap-6 md:grid-cols-5">
                        @csrf

                        <!-- Selector de día como píldoras -->
                        <div class="md:col-span-3">
                            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Día de la semana
                            </label>

                            <div class="grid grid-cols-7 gap-2">
                                @php
                                $days = [
                                ['L',1,'Lunes'],
                                ['K',2,'Martes'],
                                ['M',3,'Miércoles'],
                                ['J',4,'Jueves'],
                                ['V',5,'Viernes'],
                                ['S',6,'Sábado'],
                                ['D',0,'Domingo'],
                                ];
                                @endphp

                                @foreach($days as [$short,$val,$full])
                                <button type="button" @click="day={{ $val }}"
                                    :class="day==={{ $val }} ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                                    class="px-3 py-2 rounded-lg text-sm font-medium border border-gray-200 dark:border-gray-700 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span class="sr-only">{{ $full }}</span>{{ $short }}
                                </button>
                                @endforeach
                            </div>

                            <input type="hidden" name="day_of_week" :value="day">
                            @error('day_of_week')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Hora -->
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Hora
                            </label>
                            <input type="time" name="time" x-model="time" step="300"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500">
                            @error('time')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror>

                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                * Formato 24h. Sugerencia: intervalos de 5 min.
                            </p>
                        </div>

                        <!-- Acciones -->
                        <div class="md:col-span-5 flex items-center gap-3 pt-2">
                            <button class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 shadow">
                                Guardar
                            </button>
                            <a href="{{ route('schedules.index') }}"
                                class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                                Cancelar
                            </a>
                        </div>
                    </form>

                    <!-- Tips -->
                    <div
                        class="mt-8 rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900">
                        <h4 class="font-medium text-gray-800 dark:text-gray-200 text-sm mb-2">Consejos</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 list-disc pl-5 space-y-1">
                            <li>Agrega varias horas para reforzar la cadencia de publicaciones.</li>
                            <li>Puedes editar/eliminar desde la grilla semanal.</li>
                            <li>La opción “A la cola” usará el próximo horario disponible.</li>
                        </ul>
                    </div>
                </div>
            </div>
            @else
            <div
                class="max-w-xl mx-auto p-6 rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200">
                No tienes permisos para crear horarios.
            </div>
            @endcan
        </div>
    </div>

    <script>
    function scheduleForm(initial) {
        return {
            day: initial.day ?? 0,
            time: initial.time ?? '',
        }
    }
    </script>
</x-app-layout>