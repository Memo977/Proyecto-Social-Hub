<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('provider');               // 'mastodon', 'reddit', 'twitter', etc.
            $table->string('provider_user_id');       // ID del usuario en el proveedor
            $table->string('username')->nullable();   // @usuario en Mastodon
            $table->string('instance_domain')->nullable(); // dominio Mastodon (por si cambias de instancia)

            $table->text('access_token');             // token de acceso (luego lo ciframos en commit 29)
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->json('meta')->nullable();         // email, avatar, etc.
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']); // una identidad única por proveedor
            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
