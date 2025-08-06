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
        Schema::create('post_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|sent|failed
            $table->string('provider_post_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['post_id','social_account_id']);
            $table->index(['status','published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_targets');
    }
};