<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            {{ __('Horarios de publicación') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <a href="{{ route('schedules.create') }}"
                class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg">
                + Nuevo Horario
            </a>

            @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">Día</th>
                            <th class="px-4 py-2 text-left">Hora</th>
                            <th class="px-4 py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($schedules as $s)
                        <tr>
                            <td class="px-4 py-2">{{ $s->day_name }}</td>
                            <td class="px-4 py-2">{{ $s->time }}</td>
                            <td class="px-4 py-2 flex gap-2">
                                <a href="{{ route('schedules.edit', $s) }}"
                                    class="px-3 py-1 bg-yellow-500 text-white rounded">Editar</a>
                                <form action="{{ route('schedules.destroy', $s) }}" method="POST"
                                    onsubmit="return confirm('¿Eliminar horario?');">
                                    @csrf @method('DELETE')
                                    <button class="px-3 py-1 bg-red-600 text-white rounded">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-gray-500">
                                No tienes horarios registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>