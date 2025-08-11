<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\SocialAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $postId;

    /**
     * @param  int  $postId
     */
    public function __construct(int $postId)
    {
        $this->postId = $postId;
        // puedes ajustar prioridad/cola si quieres:
        // $this->onQueue('default')->onConnection('database');
    }

    /**
     * Opcional: aplicar middlewares (ej. rate limit por proveedor).
     */
    public function middleware(): array
    {
        return [
            // RateLimited::class, // si más adelante usas limitadores con nombre
        ];
    }

    public function handle(): void
    {
        try {
            /** @var \App\Models\Post $post */
            $post = \App\Models\Post::query()
                ->with(['targets.socialAccount']) // eager load para saber el provider
                ->findOrFail($this->postId);

            // Marcar estado transitorio
            if ($post->status !== 'publishing') {
                $post->update(['status' => 'publishing']);
            }

            foreach ($post->targets as $target) {
                $account = $target->socialAccount; // relación
                if (!$account) {
                    Log::warning('PublishPost: target sin socialAccount', ['target_id' => $target->id]);
                    
                    // Marcar target como fallido
                    $target->update([
                        'status' => 'failed',
                        'error' => 'No se encontró la cuenta social asociada'
                    ]);
                    continue;
                }

                $provider = $account->provider; // 'mastodon' | 'reddit' | ...

                Log::info('PublishPost: Dispatching job', [
                    'post_id' => $post->id,
                    'target_id' => $target->id,
                    'provider' => $provider
                ]);

                if ($provider === 'reddit') {
                    Log::info('PublishPost: A punto de despachar PublishToReddit', [
                        'post_id' => $post->id,
                        'target_id' => $target->id,
                        'account_id' => $account->id
                    ]);
                    \App\Jobs\PublishToReddit::dispatch($post->id, $target->id); // ✅ Pasamos solo el ID
                    Log::info('PublishPost: PublishToReddit despachado exitosamente');
                } elseif ($provider === 'mastodon') {
                    \App\Jobs\PublishToMastodon::dispatch($post->id, $target->toArray()); // ✅ Mastodon sigue usando array
                } else {
                    Log::warning('PublishPost: provider no soportado', [
                        'provider' => $provider,
                        'target_id' => $target->id,
                    ]);
                    
                    // Marcar target como fallido
                    $target->update([
                        'status' => 'failed',
                        'error' => "Provider '{$provider}' no soportado"
                    ]);
                }
            }

            Log::info('PublishPost encoló sub-jobs por proveedor', [
                'post_id' => $post->id,
                // solo para el log: listamos providers reales
                'providers' => $post->targets->map(fn($t) => optional($t->socialAccount)->provider)->filter()->values(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('PublishPost: Post no encontrado', [
                'post_id' => $this->postId,
                'error' => $e->getMessage()
            ]);
            $this->fail($e);
        } catch (\Throwable $e) {
            Log::error('PublishPost: Error inesperado', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->fail($e);
        }
    }
}