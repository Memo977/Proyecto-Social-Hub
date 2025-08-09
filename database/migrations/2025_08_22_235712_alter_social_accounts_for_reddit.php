<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('social_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('social_accounts', 'provider')) {
                $table->string('provider')->index();
            }
            if (!Schema::hasColumn('social_accounts', 'provider_user_id')) {
                $table->string('provider_user_id')->index();
            }
            if (!Schema::hasColumn('social_accounts', 'username')) {
                $table->string('username')->nullable();
            }
            if (!Schema::hasColumn('social_accounts', 'instance_domain')) {
                $table->string('instance_domain')->nullable(); // Mastodon usa esto; Reddit lo deja null
            }
            if (!Schema::hasColumn('social_accounts', 'access_token')) {
                $table->string('access_token', 2048)->nullable();
            }
            if (!Schema::hasColumn('social_accounts', 'refresh_token')) {
                $table->string('refresh_token', 2048)->nullable();
            }
            if (!Schema::hasColumn('social_accounts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (!Schema::hasColumn('social_accounts', 'meta')) {
                $table->json('meta')->nullable();
            }
        });
    }

    public function down(): void {
        // No eliminamos columnas por seguridad de datos
    }
};
