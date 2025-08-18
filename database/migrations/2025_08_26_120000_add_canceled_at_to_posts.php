<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'canceled_at')) {
                $table->timestamp('canceled_at')->nullable()->after('published_at');
            }
            // Opcional: si tu columna 'status' es ENUM o string libre,
            // no es necesario tocarla; usaremos canceled_at como flag.
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'canceled_at')) {
                $table->dropColumn('canceled_at');
            }
        });
    }
};