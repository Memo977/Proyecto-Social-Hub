<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// 👇 importa el controlador de 2FA (gestión desde el perfil)
use App\Http\Controllers\TwoFactorController;

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
});

require __DIR__.'/auth.php';