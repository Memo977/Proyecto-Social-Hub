<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Histórico de publicaciones
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('posts.queue') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium
                          bg-gray-200 text-gray-800 hover:bg-gray-300
                          dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                    Pendientes
                </a>
                <a href="{{ route('posts.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium
                          bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring
                          focus:ring-blue-500/50 dark:bg-blue-500 dark:hover:bg-blue-600">
                    Nueva publicación
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                {{-- Filtros --}}
                <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-6 gap-3">
                    <input type="text" name="q" value="{{ request('q') }}"
                        placeholder="Buscar contenido/título/enlace..."
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    <select name="provider"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">Todos los proveedores</option>
                        <option value="mastodon" @selected(request('provider')==='mastodon' )>Mastodon</option>
                        <option value="reddit" @selected(request('provider')==='reddit' )>Reddit</option>
                    </select>
                    <select name="status"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">Cualquier estado</option>
                        <option value="published" @selected(request('status')==='published' )>Publicado</option>
                        <option value="failed" @selected(request('status')==='failed' )>Falló</option>
                    </select>
                    <input type="date" name="from" value="{{ request('from') }}"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    <input type="date" name="to" value="{{ request('to') }}"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    <div class="md:col-span-1 flex gap-2">
                        <button class="px-3 py-2 rounded-md bg-gray-200 hover:bg-gray-300
                                       dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100">
                            Filtrar
                        </button>
                        <a href="{{ route('posts.history') }}" class="px-3 py-2 rounded-md bg-gray-100 hover:bg-gray-200
                                  dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-100">
                            Limpiar
                        </a>
                    </div>
                </form>

                {{-- Tabla --}}
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Publicado</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Contenido</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Proveedores</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Resumen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($posts as $post)
                            @php $s = $summaries[$post->id] ?? null; @endphp
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-200">
                                    {{ optional($post->published_at ?? $post->updated_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                    @if($post->title)
                                    <div class="font-medium">{{ \Illuminate\Support\Str::limit($post->title, 90) }}
                                    </div>
                                    @endif
                                    @if($post->content)
                                    <div class="text-gray-600 dark:text-gray-300">
                                        {{ \Illuminate\Support\Str::limit($post->content, 120) }}
                                    </div>
                                    @endif
                                    @if($post->link)
                                    <a class="text-blue-600 dark:text-blue-400 underline underline-offset-2"
                                        href="{{ $post->link }}" target="_blank" rel="noopener">enlace</a>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap gap-1.5">
                                        @php $targets = $post->targets ?? collect(); @endphp
                                        @foreach($targets as $t)
                                        @php
                                        $prov = ucfirst($t->socialAccount->provider ?? 'N/A');
                                        $st = $t->status ?? 'pending';
                                        $cls = match($st){
                                        'published' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40
                                        dark:text-emerald-200',
                                        'failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'
                                        };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $cls }}">
                                            {{ $prov }} · {{ $st }}
                                        </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($s)
                                    @php
                                    $map = [
                                    'published' => ['Publicado', 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40
                                    dark:text-emerald-200'],
                                    'partial' => ['Parcial', 'bg-amber-100 text-amber-800 dark:bg-amber-900/40
                                    dark:text-amber-200'],
                                    'failed' => ['Falló', 'bg-rose-100 text-rose-800 dark:bg-rose-900/40
                                    dark:text-rose-200'],
                                    'pending' => ['Pendiente', 'bg-gray-100 text-gray-800 dark:bg-gray-700
                                    dark:text-gray-100'],
                                    ];
                                    [$label, $cls] = $map[$s['overall']] ?? $map['pending'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $cls }}">
                                        {{ $label }} · {{ $s['published'] }}/{{ $s['total'] }}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    Aún no hay publicaciones enviadas.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $posts->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>