<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Horarios de publicación
            </h2>

            <a href="{{ route('schedules.create') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 shadow">
                + Nuevo horario
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-2xl">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr>
                                    @foreach($headers as $h)
                                    <th
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center text-sm font-semibold bg-gray-50 dark:bg-gray-900">
                                        {{ $h }}
                                    </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                <tr>
                                    @foreach($row as $cell)
                                    <td
                                        class="px-3 py-2 border border-gray-300 dark:border-gray-700 text-center align-middle text-sm">
                                        @if($cell)
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="font-medium">
                                                {{ \Illuminate\Support\Str::of($cell->time)->substr(0,5) }}
                                            </span>

                                            {{-- Acciones inline --}}
                                            <a href="{{ route('schedules.edit', $cell) }}"
                                                class="px-1.5 py-0.5 rounded text-xs bg-emerald-600 text-white hover:bg-emerald-700">
                                                Editar
                                            </a>

                                            <form action="{{ route('schedules.destroy', $cell) }}" method="POST"
                                                onsubmit="return confirm('¿Eliminar horario {{ \Illuminate\Support\Str::of($cell->time)->substr(0,5) }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="px-1.5 py-0.5 rounded text-xs bg-red-600 text-white hover:bg-red-700">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                        @else
                                        <span class="opacity-40">—</span>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ count($headers) }}"
                                        class="px-3 py-6 text-center text-sm text-gray-500">
                                        Aún no tienes horarios. Crea al menos uno con el botón “Nuevo horario”.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        * Se muestran las horas en formato HH:MM. Puedes registrar varias horas por día; se listan de
                        arriba hacia abajo.
                    </p>

                    <div class="mt-6 flex items-center gap-3">
                        <a href="{{ route('schedules.create') }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 shadow">
                            Añadir horario
                        </a>
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                            ← Volver al dashboard
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>