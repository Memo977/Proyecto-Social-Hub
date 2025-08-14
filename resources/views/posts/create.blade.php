<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Nueva publicación
            </h2>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                ← Volver al dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6" x-data="postCreate()">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">

                {{-- Flash / errores --}}
                @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-3 text-green-800 dark:text-green-200">
                    {{ session('status') }}
                </div>
                @endif
                @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 p-3 text-red-800 dark:text-red-200">
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

                    {{-- Modo --}}
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-2">Modo</h3>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="mode" value="now" x-model="mode"
                                    @checked(old('mode','now')==='now' )>
                                <span class="text-sm text-gray-700 dark:text-gray-200">Publicar ahora</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" name="mode" value="scheduled" x-model="mode"
                                    @checked(old('mode')==='scheduled' )>
                                <span class="text-sm text-gray-700 dark:text-gray-200">Programar (fecha/hora
                                    exacta)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 opacity-50 cursor-not-allowed"
                                title="Se habilita en otro commit">
                                <input type="radio" name="mode" value="queue" disabled>
                                <span class="text-sm text-gray-700 dark:text-gray-200">A la cola (próximo
                                    horario)</span>
                            </label>
                        </div>
                    </div>

                    {{-- Fecha/Hora para programada --}}
                    <div class="mb-6" x-show="mode === 'scheduled'">
                        <x-input-label for="scheduled_at" value="Fecha y hora (tu zona horaria)" />
                        <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local"
                            class="mt-1 block w-full" value="{{ old('scheduled_at') }}" />
                        <p class="text-xs text-gray-500 mt-1">
                            Sugerencia: APP_TIMEZONE=America/Costa_Rica en tu .env
                        </p>
                    </div>

                    {{-- Contenido --}}
                    <div class="mb-6">
                        <x-input-label for="content" value="Contenido" />
                        <textarea id="content" name="content" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">{{ old('content') }}</textarea>
                    </div>

                    {{-- Media opcional --}}
                    <div class="mb-6">
                        <x-input-label for="media_url" value="URL de imagen/video (opcional)" />
                        <x-text-input id="media_url" name="media_url" type="url" class="mt-1 block w-full"
                            value="{{ old('media_url') }}" x-model="mediaUrl" />
                        <p class="text-xs text-gray-500 mt-1">
                            Para Mastodon y Reddit (self con imagen). Imágenes: JPG/PNG/GIF/WebP · Videos: MP4/WebM
                        </p>

                        <div class="mt-2" x-show="mediaUrl && isImageUrl(mediaUrl)" x-cloak>
                            <p class="text-xs text-gray-600 mb-1">Vista previa:</p>
                            <img :src="mediaUrl" alt="Preview" class="max-w-xs max-h-32 rounded border"
                                x-on:error="$event.target.style.display='none'">
                        </div>
                    </div>

                    {{-- Destinos --}}
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-2">Destinos</h3>

                        {{-- Mastodon --}}
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Mastodon</p>
                            @forelse(($accounts['mastodon'] ?? []) as $acc)
                            <label class="flex items-center gap-2 mb-2">
                                <input type="checkbox" name="targets[]" value="{{ $acc->id }}"
                                    @checked(collect(old('targets',[]))->contains($acc->id))>
                                <span class="text-sm text-gray-700 dark:text-gray-200">
                                    {{ $acc->username }} – {{ $acc->instance_domain }}
                                </span>
                            </label>
                            @empty
                            <p class="text-xs text-gray-400">No tienes Mastodon conectado.</p>
                            @endforelse
                        </div>

                        {{-- Reddit --}}
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Reddit</p>

                            @forelse(($accounts['reddit'] ?? []) as $acc)
                            <label class="flex items-center gap-2 mb-2">
                                <input type="checkbox" name="targets[]" value="{{ $acc->id }}" data-provider="reddit"
                                    x-on:change="updateRedditSelected()"
                                    @checked(collect(old('targets',[]))->contains($acc->id))>
                                <span class="text-sm text-gray-700 dark:text-gray-200">
                                    {{ $acc->username }} – {{ $acc->instance_domain ?? 'reddit.com' }}
                                </span>
                            </label>
                            @empty
                            <p class="text-xs text-gray-400">No tienes Reddit conectado.</p>
                            @endforelse

                            {{-- Campos extra Reddit (solo si hay Reddit seleccionado) --}}
                            <div class="grid md:grid-cols-2 gap-4 mt-3" x-show="reddit" x-cloak>
                                <div>
                                    <x-input-label for="reddit_subreddit" value="Subreddit" />
                                    <x-text-input id="reddit_subreddit" name="reddit_subreddit" type="text"
                                        class="mt-1 block w-full" value="{{ old('reddit_subreddit') }}"
                                        placeholder="ej. r/test o u_tu_usuario" />
                                </div>

                                <div>
                                    <x-input-label for="reddit_title" value="Título (obligatorio en Reddit)" />
                                    <x-text-input id="reddit_title" name="reddit_title" type="text"
                                        class="mt-1 block w-full" value="{{ old('reddit_title') }}" />
                                </div>

                                <div>
                                    <x-input-label for="reddit_kind" value="Tipo" />
                                    <select id="reddit_kind" name="reddit_kind"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                        x-model="kind">
                                        <option value="self" @selected(old('reddit_kind','self')==='self' )>self (texto
                                            / puede llevar imagen)</option>
                                        <option value="link" @selected(old('reddit_kind')==='link' )>link (enlace)
                                        </option>
                                    </select>
                                </div>

                                <div class="md:col-span-2" x-show="kind === 'link'">
                                    <x-input-label for="reddit_url" value="URL del enlace" />
                                    <x-text-input id="reddit_url" name="reddit_url" type="url" class="mt-1 block w-full"
                                        value="{{ old('reddit_url') }}" placeholder="https://ejemplo.com" />
                                    <p class="text-xs text-gray-500 mt-1">
                                        Esta URL será el enlace principal del post.
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                                        <p class="text-sm text-blue-800 dark:text-blue-200 mb-1">
                                            <strong>Tipos de post en Reddit:</strong>
                                        </p>
                                        <ul
                                            class="text-xs text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                                            <li><strong>self:</strong> Texto; si agregas una imagen en “URL de
                                                imagen/video” se embebe en el cuerpo.</li>
                                            <li><strong>link:</strong> Post de enlace a una URL externa.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button x-text="mode === 'scheduled' ? 'Programar' : 'Publicar ahora'">
                            Publicar
                        </x-primary-button>
                    </div>
                </form>
                @else
                <div class="p-4 rounded bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200">
                    No tienes permisos para crear publicaciones.
                </div>
                @endcan
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('postCreate', () => ({
            // Valores restaurados con old()
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
            }
        }));
    });
    </script>
</x-app-layout>