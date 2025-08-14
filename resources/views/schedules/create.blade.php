<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            {{ __('Nuevo horario') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            @can('create', App\Models\PublicationSchedule::class)
            <form action="{{ route('schedules.store') }}" method="POST"
                class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
                @csrf

                <div class="mb-4">
                    <label class="block mb-1">Día de la semana</label>
                    <select name="day_of_week" class="w-full border-gray-300 rounded">
                        @foreach(['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'] as $i => $name)
                        <option value="{{ $i }}" @selected(old('day_of_week')==$i)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('day_of_week') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-1">Hora</label>
                    <input type="time" name="time" value="{{ old('time') }}" class="w-full border-gray-300 rounded">
                    @error('time') <p class="text-red-600">{{ $message }}</p> @enderror
                </div>

                <button class="px-4 py-2 bg-blue-600 text-white rounded">Guardar</button>
                <a href="{{ route('schedules.index') }}" class="ml-2">Cancelar</a>
            </form>
            @else
            <div class="p-4 rounded bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200">
                No tienes permisos para crear horarios.
            </div>
            @endcan
        </div>
    </div>
</x-app-layout>