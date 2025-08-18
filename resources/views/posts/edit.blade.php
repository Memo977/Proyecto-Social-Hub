{{-- resources/views/posts/edit.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Editar publicación
            </h2>
            <a href="{{ route('posts.queue') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                ← Volver a pendientes
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                @if (session('error'))
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-red-800 dark:text-red-200">
                    {{ session('error') }}
                </div>
                @endif

                <form method="POST" action="{{ route('posts.update', $post) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Contenido --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contenido</label>
                            <textarea name="content" rows="5"
                                class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">{{ old('content', $post->content) }}</textarea>
                            @error('content')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Campos condicionales: Reddit --}}
                        @php
                        /** @var \App\Models\Post $post */
                        $hasRedditTarget = $post->targets->contains(
                        fn ($t) => optional($t->socialAccount)->provider === 'reddit'
                        );
                        $redditKind = old('reddit_kind', data_get($post->meta, 'reddit.kind', 'self'));
                        $redditSubreddit = old('reddit_subreddit', data_get($post->meta, 'reddit.subreddit'));
                        @endphp

                        @if ($hasRedditTarget)
                        <div class="rounded-md border border-gray-200 dark:border-gray-700 p-4">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">
                                Campos adicionales para Reddit
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="reddit_subreddit"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Subreddit o perfil (r/ejemplo o u/usuario)
                                    </label>
                                    <input id="reddit_subreddit" type="text" name="reddit_subreddit"
                                        value="{{ $redditSubreddit }}"
                                        class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100"
                                        placeholder="r/test o u/tu_usuario">
                                    @error('reddit_subreddit')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-1">
                                        Si lo dejas vacío, publicaremos en tu perfil (<code>u/tu_usuario</code>).
                                    </p>
                                </div>

                                <div>
                                    <label for="reddit_kind"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Tipo
                                    </label>
                                    <select id="reddit_kind" name="reddit_kind"
                                        class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">
                                        <option value="self" @selected($redditKind==='self' )>self (texto / puede llevar
                                            imagen)</option>
                                        <option value="link" @selected($redditKind==='link' )>link (enlace)</option>
                                    </select>
                                    @error('reddit_kind')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="title"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Título (obligatorio en Reddit)
                                    </label>
                                    <input id="title" type="text" name="title" value="{{ old('title', $post->title) }}"
                                        class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">
                                    @error('title')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="md:col-span-2">
                                    <label for="link"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        URL del enlace (solo para tipo "link")
                                    </label>
                                    <input id="link" type="url" name="link" value="{{ old('link', $post->link) }}"
                                        class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">
                                    @error('link')
                                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @else
                        {{-- Si NO tiene Reddit: campos simples de Título/Link --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                <input type="text" name="title" value="{{ old('title', $post->title) }}"
                                    class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">
                                @error('title')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Link</label>
                                <input type="url" name="link" value="{{ old('link', $post->link) }}"
                                    class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">
                                @error('link')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        @endif

                        {{-- Media URL --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Media URL</label>
                            <input type="url" name="media_url" value="{{ old('media_url', $post->media_url) }}"
                                class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100">
                            @error('media_url')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Modo + Programación (select de horarios del usuario) --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Modo</label>
                                <select name="mode" id="mode"
                                    class="mt-1 w-full rounded-md dark:bg-gray-900 dark:text-gray-100" x-data
                                    x-on:change="
                                            document.getElementById('scheduled_at_wrap').classList.toggle('hidden', this.value !== 'schedule');
                                        ">
                                    <option value="now" {{ old('mode', $post->mode) === 'now' ? 'selected' : '' }}>Ahora
                                    </option>
                                    <option value="queue" {{ old('mode', $post->mode) === 'queue' ? 'selected' : '' }}>
                                        Cola</option>
                                    <option value="schedule"
                                        {{ old('mode', $post->mode) === 'schedule' ? 'selected' : '' }}>Programar
                                    </option>
                                </select>
                                @error('mode')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div id="scheduled_at_wrap"
                                class="{{ old('mode', $post->mode) === 'schedule' ? '' : 'hidden' }}">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Selecciona uno de tus horarios
                                </label>

                                @php
                                $dayLabels =
                                [0=>'Domingo',1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
                                $mySchedules = ($schedules ?? collect());
                                @endphp

                                @if ($mySchedules->isNotEmpty())
                                <select id="schedule_option" name="schedule_option"
                                    class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                    <option value="">— Selecciona un horario —</option>
                                    @foreach ($mySchedules as $slot)
                                    @php $hhmm = \Illuminate\Support\Str::of($slot->time)->substr(0,5); @endphp
                                    <option value="{{ $slot->id }}" @selected(old('schedule_option')==$slot->id)>
                                        {{ $dayLabels[$slot->day_of_week] ?? $slot->day_of_week }} — {{ $hhmm }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('schedule_option')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-2">
                                    Se programará para la <strong>próxima ocurrencia</strong> de ese día y hora (según
                                    tu zona horaria).
                                </p>
                                @else
                                <div
                                    class="mt-1 p-3 rounded-md bg-amber-50 dark:bg-amber-900/20 text-sm text-amber-800 dark:text-amber-200">
                                    Aún no tienes horarios. <a href="{{ route('schedules.create') }}"
                                        class="underline">Crea al menos uno</a> para poder programar.
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <a href="{{ route('posts.queue') }}"
                            class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                            Cancelar
                        </a>
                        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>