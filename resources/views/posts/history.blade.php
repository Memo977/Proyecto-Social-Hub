<x-app-layout>

    <style>
    /* Oculta el indicador nativo y evita que capture clics */
    input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 0;
        pointer-events: none;
    }

    /* Opcional: modo oscuro para el input */
    .dark input[type="date"] {
        color-scheme: dark;
    }
    </style>

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-gray-100 leading-tight">
                Histórico de publicaciones
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('posts.queue') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium
                          bg-gray-200 text-gray-900 hover:bg-gray-300 transition-colors
                          dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pendientes
                </a>
                @can('create', App\Models\Post::class)
                <a href="{{ route('posts.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium
                          bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2
                          focus:ring-blue-500/20 transition-all dark:bg-blue-500 dark:hover:bg-blue-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva publicación
                </a>
                @endcan
            </div>
        </div>
    </x-slot>

    @php
    // === Badges coherentes con el dashboard ===
    $overallMap = [
    'published' => ['Publicado', 'bg-emerald-500/10 text-emerald-300 ring-1 ring-emerald-400/30'],
    'partial' => ['Parcial', 'bg-amber-500/10 text-amber-300 ring-1 ring-amber-400/30'],
    'failed' => ['Falló', 'bg-rose-500/10 text-rose-300 ring-1 ring-rose-400/30'],
    'pending' => ['Pendiente', 'bg-gray-500/10 text-gray-300 ring-1 ring-gray-400/30'],
    ];
    $statusIcon = [
    'published' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>',
    'failed' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>',
    'pending' => '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>',
    ];
    // Pastillas por proveedor (color de marca, no por estado)
    function providerPillClasses($p) {
    return match(strtolower($p)) {
    'mastodon' => 'bg-indigo-500/10 text-indigo-300 ring-1 ring-indigo-400/30',
    'reddit' => 'bg-orange-500/10 text-orange-300 ring-1 ring-orange-400/30',
    default => 'bg-gray-500/10 text-gray-300 ring-1 ring-gray-400/30',
    };
    }
    function providerDot($p) {
    return match(strtolower($p)) {
    'mastodon' => 'bg-indigo-400',
    'reddit' => 'bg-orange-400',
    default => 'bg-gray-400',
    };
    }
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Card contenedor -->
            <div class="rounded-2xl overflow-hidden border border-gray-200/60 dark:border-gray-800/70 shadow-sm
                        bg-gray-50 dark:bg-gray-900/50">

                <!-- Filtros -->
                <div class="border-b border-gray-200/60 dark:border-gray-800/70 bg-white/60 dark:bg-gray-900/40 p-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Filtros de búsqueda</h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label
                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar</label>
                            <input type="text" name="q" value="{{ request('q') }}"
                                placeholder="Contenido, título o enlace..." class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100
                                          focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm" />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Proveedor</label>
                            <select name="provider" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100
                                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm">
                                <option value="">Todos</option>
                                <option value="mastodon" @selected(request('provider')==='mastodon' )>Mastodon</option>
                                <option value="reddit" @selected(request('provider')==='reddit' )>Reddit</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                            <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100
                                           focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm">
                                <option value="">Cualquier estado</option>
                                <option value="published" @selected(request('status')==='published' )>Publicado</option>
                                <option value="failed" @selected(request('status')==='failed' )>Falló</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                            <div class="relative">
                                <input id="fromDate" type="date" name="from" value="{{ request('from') }}" class="w-full pr-10 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100
             focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm" />
                                <button type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-md hover:bg-gray-500/20 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    onclick="(function(btn){const i=document.getElementById('fromDate'); if(i){ if('showPicker' in i){ i.showPicker(); } else { i.focus(); } } })(this)">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" stroke-width="2" />
                                        <line x1="8" y1="2" x2="8" y2="6" stroke-width="2" />
                                        <line x1="3" y1="10" x2="21" y2="10" stroke-width="2" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                            <div class="relative">
                                <input id="toDate" type="date" name="to" value="{{ request('to') }}" class="w-full pr-10 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100
             focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm" />
                                <button type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded-md hover:bg-gray-500/20 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                    onclick="(function(btn){const i=document.getElementById('toDate'); if(i){ if('showPicker' in i){ i.showPicker(); } else { i.focus(); } } })(this)">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-width="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" stroke-width="2" />
                                        <line x1="8" y1="2" x2="8" y2="6" stroke-width="2" />
                                        <line x1="3" y1="10" x2="21" y2="10" stroke-width="2" />
                                    </svg>
                                </button>
                            </div>
                        </div>


                        <div class="flex items-end gap-2">
                            <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium
                                           transition-colors focus:ring-2 focus:ring-blue-500/20">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                Filtrar
                            </button>
                            <a href="{{ route('posts.history') }}" class="px-3 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm
                                      dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="sticky top-0 z-10 bg-black border-b border-white/10">
                            <tr class="text-left">
                                <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-white/90">
                                    Publicado</th>
                                <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-white/90">
                                    Contenido</th>
                                <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-white/90">
                                    Proveedores</th>
                                <th class="px-6 py-3 text-[11px] font-semibold uppercase tracking-wider text-white/90">
                                    Resumen</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100/70 dark:divide-gray-800/70">
                            @forelse($posts as $post)
                            @php $s = $summaries[$post->id] ?? null; @endphp
                            <tr
                                class="bg-white/30 dark:bg-transparent hover:bg-gray-50/70 dark:hover:bg-gray-800/60 transition-colors">
                                <!-- Fecha -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ optional($post->published_at ?? $post->updated_at)->timezone(config('app.timezone'))->format('d/m/Y') }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ optional($post->published_at ?? $post->updated_at)->timezone(config('app.timezone'))->format('H:i') }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Contenido -->
                                <td class="px-6 py-4 align-top">
                                    <div class="max-w-md space-y-1.5">
                                        @if($post->title)
                                        <div class="font-medium text-gray-900 dark:text-gray-100 leading-5">
                                            {{ \Illuminate\Support\Str::limit($post->title, 80) }}
                                        </div>
                                        @endif
                                        @if($post->content)
                                        <div class="text-sm text-gray-600 dark:text-gray-400 leading-5">
                                            {{ \Illuminate\Support\Str::limit($post->content, 100) }}
                                        </div>
                                        @endif
                                        @if($post->link)
                                        <a class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium"
                                            href="{{ $post->link }}" target="_blank" rel="noopener">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            Ver enlace
                                        </a>
                                        @endif
                                    </div>
                                </td>

                                <!-- Proveedores -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @php $targets = $post->targets ?? collect(); @endphp
                                        @foreach($targets as $t)
                                        @php
                                        $provName = ucfirst($t->socialAccount->provider ?? 'N/A');
                                        $provCls = providerPillClasses($provName);
                                        $dotCls = providerDot($provName);
                                        $st = $t->status ?? 'pending';
                                        $ico = $statusIcon[$st] ?? $statusIcon['pending'];
                                        @endphp
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $provCls }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $dotCls }}"></span>
                                            {!! $ico !!}
                                            {{ $provName }}
                                        </span>
                                        @endforeach
                                    </div>
                                </td>

                                <!-- Resumen -->
                                <td class="px-6 py-4">
                                    @if($s)
                                    @php
                                    [$label, $cls] = $overallMap[$s['overall']] ?? $overallMap['pending'];
                                    @endphp
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $cls }}">
                                            {!! $statusIcon[$s['overall']] ?? $statusIcon['pending'] !!}
                                            {{ $label }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $s['published'] }}/{{ $s['total'] }} completados
                                        </span>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                        <svg class="w-12 h-12 mb-4 text-gray-300 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <h3 class="text-sm font-medium mb-1">No hay publicaciones</h3>
                                        <p class="text-sm">Aún no hay publicaciones enviadas para mostrar.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if($posts->hasPages())
                <div
                    class="border-t border-gray-200/60 dark:border-gray-800/70 bg-white/60 dark:bg-gray-900/40 px-6 py-4">
                    {{ $posts->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>