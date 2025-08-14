<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            {{ __('Editar horario') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            @can('update', $schedule)
            <form action="{{ route('schedules.update', $schedule) }}" method="POST"
                class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label class="block mb-1">Día de la semana</label>
                    <select name="day_of_week" class="w-full border-gray-300 rounded">
                        @foreach(['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'] as $i => $name)
                        <option value="{{ $i }}" @selected($schedule->day_of_week == $i)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('day_of_week') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-1">Hora</label>
                    <input type="time" name="time" value="{{ $schedule->time }}" class="w-full border-gray-300 rounded">
                    @error('time') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2">
                    <button class="px-4 py-2 bg-blue-600 text-white rounded">Actualizar</button>
                    @can('delete', $schedule)
                    <form action="{{ route('schedules.destroy', $schedule) }}" method="POST"
                        onsubmit="return confirm('¿Eliminar horario?');">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 bg-red-600 text-white rounded">Eliminar</button>
                    </form>
                    @endcan
                    <a href="{{ route('schedules.index') }}" class="ml-auto">Cancelar</a>
                </div>
            </form>
            @else
            <div class="p-4 rounded bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200">
                No tienes permisos para editar este horario.
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>