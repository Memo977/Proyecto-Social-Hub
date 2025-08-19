<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900 dark:text-gray-100 leading-tight">
                    Nueva publicación
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Redacta tu contenido, elige destinos y programa
                    si lo necesitas.</p>
            </div>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-medium
                      bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                ← Volver al panel
            </a>
        </div>
    </x-slot>

    <style>
    [x-cloak] {
        display: none !important;
    }
    </style>

    <div class="py-6" x-data="postCreate()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Flash / errores (sin cambiar tu lógica) --}}
            @if (session('status'))
            <div
                class="mb-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-800 dark:text-emerald-200">
                {{ session('status') }}
            </div>
            @endif
            @if ($errors->any())
            <div class="mb-4 rounded-xl bg-rose-50 dark:bg-rose-900/20 p-3 text-rose-800 dark:text-rose-200">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @can('create', App\Models\Post::class)
            <form method="POST" action="{{ route('posts.store') }}">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Columna izquierda (contenido + opciones por red) --}}
                    <div class="lg:col-span-2 space-y-6">
                        {{-- Card: Contenido --}}
                        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contenido</h3>

                            {{-- Título (tu lógica: sólo requerido si Reddit está seleccionado) --}}
                            <div class="mb-5">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Título <span class="text-xs text-gray-500">(obligatorio en Reddit)</span>
                                </label>
                                <input id="title" name="title" type="text"
                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                                    x-bind:required="reddit" value="{{ old('title') }}"
                                    placeholder="Escribe un título breve">
                            </div>

                            {{-- Mensaje --}}
                            <div class="mb-5">
                                <label for="content"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200">Mensaje</label>
                                <textarea id="content" name="content" rows="8"
                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="¿Qué quieres publicar?">{{ old('content') }}</textarea>
                            </div>
                        </div>

                        {{-- Card: Opciones específicas por red (Reddit) --}}
                        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Opciones específicas
                                por red</h3>

                            <div class="grid md:grid-cols-2 gap-4" x-show="reddit" x-cloak>
                                <div>
                                    <label for="reddit_subreddit"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Subreddit o perfil (r/ejemplo o u/usuario)
                                    </label>
                                    <input id="reddit_subreddit" name="reddit_subreddit" type="text"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                                        value="{{ old('reddit_subreddit') }}" placeholder="r/test o u/tu_usuario">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Si lo dejas vacío, publicaremos en tu perfil (<code>u/tu_usuario</code>).
                                    </p>
                                </div>

                                <div>
                                    <label for="reddit_kind"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        Tipo (controla si muestras link)
                                    </label>
                                    <select id="reddit_kind" name="reddit_kind"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        x-model="kind">
                                        <option value="self" @selected(old('reddit_kind','self')==='self' )>self (texto
                                            / puede llevar imagen)</option>
                                        <option value="link" @selected(old('reddit_kind')==='link' )>link (enlace)
                                        </option>
                                    </select>
                                </div>

                                <div class="md:col-span-2" x-show="kind === 'link'">
                                    <label for="link"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                        URL del enlace
                                    </label>
                                    <input id="link" name="link" type="url"
                                        class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                                        x-bind:required="reddit && kind === 'link'" value="{{ old('link') }}"
                                        placeholder="https://ejemplo.com">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Si completas este campo, el post de Reddit se enviará como “link”.
                                    </p>
                                </div>
                            </div>

                            {{-- Nota cuando no hay Reddit seleccionado (opcional) --}}
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-show="!reddit">
                                Selecciona una cuenta de Reddit en “Destinos” para ver sus opciones.
                            </p>
                        </div>
                    </div>

                    {{-- Columna derecha (destinos + programación + acciones) --}}
                    <div class="space-y-6">
                        {{-- Card: Destinos (estilo dashboard, sin mostrar cuenta; verde=conectado, rojo=no conectado) --}}
                        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Destinos</h3>

                            @php
                            $mastodon = ($accounts['mastodon'] ?? collect());
                            $reddit = ($accounts['reddit'] ?? collect());
                            $hasM = $mastodon->isNotEmpty();
                            $hasR = $reddit->isNotEmpty();
                            @endphp

                            <div class="space-y-3">
                                {{-- Mastodon --}}
                                @if($hasM)
                                @foreach($mastodon as $acc)
                                <label
                                    class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                        <span
                                            class="text-sm font-medium text-gray-800 dark:text-gray-200">Mastodon</span>
                                    </div>
                                    <input type="checkbox" name="targets[]" value="{{ $acc->id }}"
                                        @checked(collect(old('targets',[]))->contains($acc->id))
                                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-blue-600
                                    focus:ring-blue-500">
                                </label>
                                @endforeach
                                @else
                                <div
                                    class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                        <span
                                            class="text-sm font-medium text-gray-800 dark:text-gray-200">Mastodon</span>
                                    </div>
                                    <a href="{{ route('oauth.mastodon.redirect') }}"
                                        class="text-xs px-2 py-1 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                                        Conectar
                                    </a>
                                </div>
                                @endif

                                {{-- Reddit --}}
                                @if($hasR)
                                @foreach($reddit as $acc)
                                <label
                                    class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Reddit</span>
                                    </div>
                                    <input type="checkbox" name="targets[]" value="{{ $acc->id }}"
                                        data-provider="reddit" x-on:change="updateRedditSelected()"
                                        @checked(collect(old('targets',[]))->contains($acc->id))
                                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-blue-600
                                    focus:ring-blue-500">
                                </label>
                                @endforeach
                                @else
                                <div
                                    class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Reddit</span>
                                    </div>
                                    <a href="{{ route('oauth.reddit.redirect') }}"
                                        class="text-xs px-2 py-1 rounded-md bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                                        Conectar
                                    </a>
                                </div>
                                @endif
                            </div>

                            @error('targets')
                            <p class="mt-2 text-sm text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>


                        {{-- Card: Programación (vertical como Destinos) --}}
                        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Programación</h3>

                            {{-- Opciones en fila vertical --}}
                            <div class="space-y-3">
                                {{-- Publicar ahora --}}
                                {{-- Publicar ahora --}}
                                <label
                                    class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer"
                                    :class="mode==='now' ? 'border-blue-300 bg-white/50 dark:bg-gray-900/40' : 'border-gray-200 dark:border-gray-700'">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full"
                                            :class="mode==='now' ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-600'"></span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Publicar
                                            ahora</span>
                                    </div>
                                    <input type="radio" name="mode" value="now" x-model="mode"
                                        @checked(old('mode','now')==='now' )
                                        class="rounded border-gray-300 dark:border-gray-600">
                                </label>

                                {{-- Programar --}}
                                <label
                                    class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer"
                                    :class="mode==='schedule' ? 'border-blue-300 bg-white/50 dark:bg-gray-900/40' : 'border-gray-200 dark:border-gray-700'">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full"
                                            :class="mode==='schedule' ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-600'"></span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Programar
                                            (fecha/hora exacta)</span>
                                    </div>
                                    <input type="radio" name="mode" value="schedule" x-model="mode"
                                        @checked(old('mode')==='schedule' )
                                        class="rounded border-gray-300 dark:border-gray-600">
                                </label>

                                {{-- A la cola --}}
                                <label
                                    class="flex items-center justify-between p-3 rounded-xl border transition cursor-pointer"
                                    :class="mode==='queue' ? 'border-blue-300 bg-white/50 dark:bg-gray-900/40' : 'border-gray-200 dark:border-gray-700'">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full"
                                            :class="mode==='queue' ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-600'"></span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">A la cola
                                            (próximo horario)</span>
                                    </div>
                                    <input type="radio" name="mode" value="queue" x-model="mode"
                                        @checked(old('mode')==='queue' )
                                        class="rounded border-gray-300 dark:border-gray-600">
                                </label>

                            </div>

                            @php
                            $dayLabels =
                            [0=>'Domingo',1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
                            $mySchedules = ($schedules ?? collect());
                            @endphp

                            {{-- Selector de horario cuando eliges "Programar" --}}
                            <div class="mt-5 space-y-2" x-show="mode === 'schedule'">
                                @if($mySchedules->isNotEmpty())
                                <label for="schedule_option"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    Selecciona uno de tus horarios
                                </label>
                                <select id="schedule_option" name="schedule_option"
                                    class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">— Selecciona un horario —</option>
                                    @foreach($mySchedules as $slot)
                                    @php $hhmm = \Illuminate\Support\Str::of($slot->time)->substr(0,5); @endphp
                                    <option value="{{ $slot->id }}" @selected(old('schedule_option')==$slot->id)>
                                        {{ $dayLabels[$slot->day_of_week] }} — {{ $hhmm }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('schedule_option')
                                <p class="text-sm text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Se programará para la <strong>próxima ocurrencia</strong> de ese día y hora (según
                                    tu zona horaria).
                                </p>
                                @else
                                <div
                                    class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-sm text-amber-800 dark:text-amber-200">
                                    Aún no tienes horarios. <a href="{{ route('schedules.create') }}"
                                        class="underline">Crea al menos uno</a> para poder programar.
                                </div>
                                @endif
                            </div>
                        </div>



                        {{-- Card: Acciones --}}
                        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acciones</h3>
                            <div class="flex flex-col gap-3">
                                <x-primary-button x-text="buttonText()" class="w-full justify-center text-center">
                                    Publicar
                                </x-primary-button>
                                <button type="reset"
                                    class="inline-flex justify-center items-center px-4 py-2 rounded-xl text-sm font-medium
                                               bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            @else
            <div class="mt-6 p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200">
                No tienes permisos para crear publicaciones.
            </div>
            @endcan
        </div>
    </div>

    {{-- Script Alpine ORIGINAL (tal cual tu lógica) --}}
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('postCreate', () => ({
            mode: @json(old('mode', 'now')),
            mediaUrl: @json(old('media_url')),
            reddit: false,
            kind: @json(old('reddit_kind', 'self')),

            init() {
                this.updateRedditSelected();
            },

            updateRedditSelected() {
                const redditCheckboxes = document.querySelectorAll(
                    'input[name="targets[]"][data-provider="reddit"]');
                this.reddit = Array.from(redditCheckboxes).some(c => c.checked);
            },

            isImageUrl(url) {
                if (!url) return false;
                const clean = url.split('?')[0];
                const ext = (clean.split('.').pop() || '').toLowerCase();
                return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
            },

            buttonText() {
                if (this.mode === 'schedule') return 'Programar';
                if (this.mode === 'queue') return 'Enviar a la cola';
                return 'Publicar ahora';
            }
        }));
    });
    </script>
</x-app-layout>