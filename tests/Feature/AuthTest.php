<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;

it('registers and sends email verification', function () {
    Notification::fake();

    $resp = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $resp->assertRedirect(); // should go to verification notice
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

    $user = User::where('email', 'test@example.com')->first();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('logs in verified users and redirects to dashboard', function () {
    $user = User::factory()->create([
        'email' => 'verified@example.com',
        'email_verified_at' => now(),
        'password' => Hash::make('secret'),
    ]);

    $resp = $this->post('/login', [
        'email' => 'verified@example.com',
        'password' => 'secret',
    ]);

    $resp->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('blocks unverified users from dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);
    $resp = $this->get('/dashboard');

    $resp->assertRedirect(route('verification.notice'));
});
