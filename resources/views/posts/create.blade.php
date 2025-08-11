<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Nueva publicación (Ahora)
            </h2>
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium bg-gray-200 text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600">
                ← Volver al dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
                @if ($errors->any())
                <div class="mb-4 text-sm text-red-600 dark:text-red-400">
                    <ul class="list-disc ms-6">
                        @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('posts.store') }}" x-data="postCreate()"
                    x-init="updateRedditSelected()" x-ref="form">
                    @csrf

                    {{-- Contenido --}}
                    <div class="mb-4">
                        <x-input-label for="content" value="Contenido" />
                        <textarea id="content" name="content" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            required>{{ old('content') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Máx. 2000 caracteres.</p>
                    </div>

                    {{-- Media opcional --}}
                    <div class="mb-6">
                        <x-input-label for="media_url" value="URL de imagen/video (opcional)" />
                        <x-text-input id="media_url" name="media_url" type="url" class="mt-1 block w-full"
                            value="{{ old('media_url') }}" x-model="mediaUrl" />
                        <p class="text-xs text-gray-500 mt-1">
                            La imagen se subirá directamente a cada plataforma para mejor compatibilidad.
                            <br>Formatos soportados: JPG, PNG, GIF, WebP (imágenes) | MP4, WebM (videos para Mastodon)
                        </p>

                        {{-- Preview de imagen --}}
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
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                                Mastodon
                                <span class="text-xs text-gray-500">(Imágenes se suben automáticamente)</span>
                            </p>
                            @forelse(($accounts['mastodon'] ?? []) as $acc)
                            <label class="flex items-center gap-2 mb-2">
                                <input type="checkbox" name="targets[]" value="{{ $acc->id }}"
                                    @checked(collect(old('targets',[]))->contains($acc->id))>
                                <span class="text-sm text-gray-700 dark:text-gray-200">
                                    {{ $acc->username }} — {{ $acc->instance_domain }}
                                </span>
                            </label>
                            @empty
                            <p class="text-xs text-gray-400">No tienes Mastodon conectado.</p>
                            @endforelse
                        </div>

                        {{-- Reddit --}}
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                                Reddit
                                <span class="text-xs text-gray-500">(Las imágenes se pueden subir como posts de
                                    imagen)</span>
                            </p>
                            @forelse(($accounts['reddit'] ?? []) as $acc)
                            <label class="flex items-center gap-2 mb-2">
                                <input type="checkbox" name="targets[]" value="{{ $acc->id }}" data-provider="reddit"
                                    x-on:change="updateRedditSelected()"
                                    @checked(collect(old('targets',[]))->contains($acc->id))>
                                <span class="text-sm text-gray-700 dark:text-gray-200">
                                    u/{{ $acc->username }}
                                </span>
                            </label>
                            @empty
                            <p class="text-xs text-gray-400">No tienes Reddit conectado.</p>
                            @endforelse
                        </div>

                        {{-- Campos extra de Reddit --}}
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3" x-cloak x-show="reddit">
                            <div>
                                <x-input-label for="reddit_subreddit" value="Subreddit (sin r/)" />
                                <x-text-input id="reddit_subreddit" name="reddit_subreddit" type="text"
                                    class="mt-1 block w-full" x-bind:disabled="!reddit" x-bind:required="reddit"
                                    value="{{ old('reddit_subreddit') }}" placeholder="laravel" />
                                <p class="text-xs text-gray-500 mt-1">Obligatorio si publicas en Reddit.</p>
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="reddit_title" value="Título" />
                                <x-text-input id="reddit_title" name="reddit_title" type="text"
                                    class="mt-1 block w-full" x-bind:disabled="!reddit" x-bind:required="reddit"
                                    value="{{ old('reddit_title') }}" />
                            </div>

                            <div>
                                <x-input-label for="reddit_kind" value="Tipo" />
                                <select id="reddit_kind" name="reddit_kind"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    x-model="kind" x-bind:disabled="!reddit">
                                    <option value="self" @selected(old('reddit_kind', 'self' )==='self' )>self (texto)
                                    </option>
                                    <option value="link" @selected(old('reddit_kind')==='link' )>link</option>
                                    <option value="image" @selected(old('reddit_kind')==='image' )>image (automático si
                                        hay imagen)</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    Si hay imagen URL y seleccionas "image", se subirá como post de imagen.
                                </p>
                            </div>

                            <div class="md:col-span-3" x-show="kind === 'link'">
                                <x-input-label for="reddit_url" value="URL (si tipo = link)" />
                                <x-text-input id="reddit_url" name="reddit_url" type="url" class="mt-1 block w-full"
                                    x-bind:disabled="!reddit || kind!=='link'" x-bind:required="reddit && kind==='link'"
                                    value="{{ old('reddit_url') }}" placeholder="https://…" />
                            </div>

                            <div class="md:col-span-4" x-show="mediaUrl && reddit">
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md">
                                    <p class="text-sm text-blue-800 dark:text-blue-200 mb-1">
                                        <strong>Comportamiento con imagen:</strong>
                                    </p>
                                    <ul
                                        class="text-xs text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                                        <li><strong>self:</strong> Imagen URL se agrega al final del texto</li>
                                        <li><strong>link:</strong> Se publica la URL especificada (ignora imagen)</li>
                                        <li><strong>image:</strong> Se sube la imagen directamente a Reddit</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>Publicar ahora</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('postCreate', () => ({
            reddit: false,
            kind: '{{ old("reddit_kind", "self") }}',
            mediaUrl: '{{ old("media_url", "") }}',

            updateRedditSelected() {
                const redditCheckboxes = this.$refs.form.querySelectorAll(
                    'input[name="targets[]"][data-provider="reddit"]');
                this.reddit = Array.from(redditCheckboxes).some(checkbox => checkbox.checked);
            },

            isImageUrl(url) {
                if (!url) return false;
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                const urlPath = url.split('?')[0];
                const extension = urlPath.split('.').pop().toLowerCase();
                return imageExtensions.includes(extension);
            }
        }));
    });
    </script>
</x-app-layout>