@php
function statusBadge($status) {
$map = [
'draft' => ['label' => 'Borrador', 'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-100'],
'queued' => ['label' => 'En cola', 'class' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300'],
'scheduled' => ['label' => 'Programado', 'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300'],
'failed' => ['label' => 'Fallido', 'class' => 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300'],
'published' => ['label' => 'Publicado', 'class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20
dark:text-emerald-300'],
'canceled' => ['label' => 'Cancelado', 'class' => 'bg-red-600 text-white dark:bg-red-500 dark:text-white'],
];
return $map[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-gray-100 text-gray-800'];
}
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900 dark:text-gray-100 leading-tight">
                    Panel general
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Resumen de tu actividad y accesos rápidos.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('posts.queue') }}"
                    class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-medium
                    bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                    Pendientes
                </a>
                <a href="{{ route('posts.history') }}"
                    class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-medium
                    bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                    Histórico
                </a>
                <a href="{{ route('posts.create') }}"
                    class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold
                    bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-500/50 dark:bg-blue-500 dark:hover:bg-blue-600">
                    Nueva publicación
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl p-5 bg-white dark:bg-gray-800 shadow-sm">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total publicaciones</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ $totalPosts }}</p>
                </div>
                <div class="rounded-2xl p-5 bg-white dark:bg-gray-800 shadow-sm">
                    <p class="text-sm text-gray-500 dark:text-gray-400">En cola</p>
                    <p class="mt-2 text-3xl font-semibold text-amber-600 dark:text-amber-400">{{ $queued }}</p>
                </div>
                <div class="rounded-2xl p-5 bg-white dark:bg-gray-800 shadow-sm">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Programadas</p>
                    <p class="mt-2 text-3xl font-semibold text-blue-600 dark:text-blue-400">{{ $scheduled }}</p>
                </div>
                <div class="rounded-2xl p-5 bg-white dark:bg-gray-800 shadow-sm">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Publicadas</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $published }}</p>
                </div>
            </div>

            {{-- Últimas publicaciones + Integraciones/Horarios --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-5 lg:col-span-2">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Últimas publicaciones</h3>
                        <a href="{{ route('posts.history') }}"
                            class="text-sm text-blue-600 hover:underline dark:text-blue-400">Ver todo</a>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($recentPosts as $p)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $p->title ?? 'Sin título' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Creado {{ $p->created_at?->diffForHumans() }}
                                    @if($p->scheduled_at)
                                    · Programado para {{ $p->scheduled_at->format('d/M H:i') }}
                                    @endif
                                    @if($p->published_at)
                                    · Publicado {{ $p->published_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                            <span
                                class="px-2.5 py-1 rounded-full text-xs font-medium {{ statusBadge($p->status)['class'] }}">
                                {{ statusBadge($p->status)['label'] }}
                            </span>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Aún no tienes publicaciones.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-5">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Integraciones</h3>
                    <div class="space-y-2">
                        @php $hasMastodon = $accounts->firstWhere('provider','mastodon'); @endphp
                        @php $hasReddit = $accounts->firstWhere('provider','reddit'); @endphp

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-2.5 w-2.5 rounded-full {{ $hasMastodon ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                </div>
                                <span class="text-sm text-gray-700 dark:text-gray-200">Mastodon</span>
                            </div>
                            @if($hasMastodon)
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">Conectado</span>
                            @else
                            <a href="{{ route('oauth.mastodon.redirect') }}"
                                class="text-xs px-2 py-1 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">Conectar</a>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="h-2.5 w-2.5 rounded-full {{ $hasReddit ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                </div>
                                <span class="text-sm text-gray-700 dark:text-gray-200">Reddit</span>
                            </div>
                            @if($hasReddit)
                            <span class="text-xs text-emerald-600 dark:text-emerald-400">Conectado</span>
                            @else
                            <a href="{{ route('oauth.reddit.redirect') }}"
                                class="text-xs px-2 py-1 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">Conectar</a>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Horarios configurados</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $schedulesC }}</span>
                        </div>
                        <a href="{{ route('schedules.index') }}"
                            class="mt-3 inline-flex items-center text-sm text-blue-600 hover:underline dark:text-blue-400">
                            Gestionar horarios
                        </a>
                    </div>
                </div>
            </div>

            {{-- Próximas programadas --}}
            <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Próximas programadas</h3>
                    <a href="{{ route('posts.queue') }}"
                        class="text-sm text-blue-600 hover:underline dark:text-blue-400">Ver cola</a>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @forelse($upcoming as $u)
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $u->title ?? 'Sin título' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Para {{ $u->scheduled_at->format('d M Y · H:i') }}
                        </p>
                    </div>
                    @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay publicaciones programadas próximas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>