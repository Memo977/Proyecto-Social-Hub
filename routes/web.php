<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 👇 importa el controlador de 2FA (gestión desde el perfil)
use App\Http\Controllers\TwoFactorController;

// OAuth Mastodon
use App\Http\Controllers\Auth\MastodonAuthController;

// OAuth Reddit
use App\Http\Controllers\Auth\RedditOAuthController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ===== Gestión de 2FA (solo usuarios autenticados) =====
    Route::get('/user/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    Route::post('/user/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/user/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
    Route::post('/user/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery');
    Route::delete('/user/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // ===== OAuth Mastodon =====
    // Requiere usuario autenticado; uso 'verified' igual que dashboard para mantener coherencia
    Route::middleware('verified')->group(function () {
        Route::get('/auth/mastodon/redirect', [MastodonAuthController::class, 'redirect'])
            ->name('oauth.mastodon.redirect');

        Route::get('/auth/mastodon/callback', [MastodonAuthController::class, 'callback'])
            ->name('oauth.mastodon.callback');
    });

    // ===== OAuth Reddit =====

    Route::middleware(['verified'])->group(function () {
        Route::get('/oauth/reddit/redirect', [RedditOAuthController::class, 'redirect'])
            ->name('oauth.reddit.redirect');

        Route::get('/oauth/reddit/callback', [RedditOAuthController::class, 'callback'])
            ->name('oauth.reddit.callback');
    });

});

require __DIR__.'/auth.php';