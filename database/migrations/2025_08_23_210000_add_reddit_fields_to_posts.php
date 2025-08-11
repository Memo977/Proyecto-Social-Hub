<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'title')) $table->string('title')->nullable()->after('content');
            if (!Schema::hasColumn('posts', 'link'))  $table->text('link')->nullable()->after('media_url');
            if (!Schema::hasColumn('posts', 'meta'))  $table->json('meta')->nullable()->after('link');
        });
    }

    public function down(): void {
        Schema::table('posts', function (Blueprint $table) {
            foreach (['title','link','meta'] as $col) {
                if (Schema::hasColumn('posts', $col)) $table->dropColumn($col);
            }
        });
    }
};