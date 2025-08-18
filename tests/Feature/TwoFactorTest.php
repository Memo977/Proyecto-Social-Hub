<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FakeGoogle2FA
{
    public function generateSecretKey($length = 32)
    {
        return str_repeat('A', $length);
    }
    public function getQRCodeUrl(...$args)
    {
        return 'otpauth://totp/...';
    }
    public function verifyKey($secret, $code)
    {
        return $secret === str_repeat('A', 32) && $code === '123456';
    }
}

it('enables and confirms 2FA with OTP', function () {
    // Bind fake 2FA service
    app()->instance('pragmarx.google2fa', new FakeGoogle2FA());

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'password' => Hash::make('pass1234'),
    ]);

    // enable
    $resp = $this->actingAs($user)->post(route('two-factor.enable'), [
        'password' => 'pass1234',
    ]);
    $resp->assertSessionHas('status');
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();

    // confirm with OTP
    $resp2 = $this->actingAs($user)->post(route('two-factor.confirm'), [
        'code' => '123456',
    ]);
    $resp2->assertSessionHas('status');
    $user->refresh();
    expect($user->two_factor_enabled)->toBeTrue();
    expect($user->two_factor_confirmed_at)->not->toBeNull();
    // codes are flashed at confirm
    $codes = session('two_factor_plain_codes');
    expect($codes)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($codes)->toHaveCount(8);
    // cada código: 10 chars alfanum mayúscula (como los generamos)
    $codes->each(function ($c) {
        expect($c)->toBeString()->and($c)->toMatch('/^[A-Z0-9]{10}$/');
    });
});