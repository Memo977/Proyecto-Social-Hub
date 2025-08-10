<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('media_url')->nullable();
            // estado general del post dentro de la app
            $table->string('status')->default('draft'); // draft|queued|scheduled|sent|failed
            // tipo de envío
            $table->string('mode')->default('now'); // now|queue|at
            $table->timestamp('scheduled_at')->nullable(); // para 'at'
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['user_id','status']);
            $table->index(['user_id','mode','scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};