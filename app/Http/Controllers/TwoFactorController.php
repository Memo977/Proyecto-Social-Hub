<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $secret = $user->two_factor_secret
            ? Crypt::decryptString($user->two_factor_secret)
            : null;

        $otpauth = null;
        if ($secret) {
            $issuer = rawurlencode(config('app.name'));
            $label  = rawurlencode($user->email);
            $otpauth = "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
        }

        return view('profile.two-factor', compact('user','secret','otpauth'));
    }

    public function enable(Request $request)
    {
        $request->validate(['password' => ['required','current_password']]);
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return back()->with('status', '2FA ya estaba activado.');
        }

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey(32);

        $codes = collect(range(1,8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain'=>$plain, 'hash'=>Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();

        session()->flash('two_factor_plain_codes', $codes->pluck('plain')->values());

        return back()->with('status', 'Escanea el QR y confirma con un código OTP.');
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required','digits:6']]);
        $user = $request->user();

        abort_unless($user->two_factor_secret, 403);

        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid = app('pragmarx.google2fa')->verifyKey($secret, $request->input('code'));

        if (! $valid) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
        ])->save();

        return back()->with('status', '2FA activado.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => ['required','current_password']]);
        $user = $request->user();
        abort_unless($user->two_factor_enabled, 403);

        $codes = collect(range(1,8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain'=>$plain, 'hash'=>Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
        ])->save();

        session()->flash('two_factor_plain_codes', $codes->pluck('plain')->values());

        return back()->with('status', 'Nuevos recovery codes generados.');
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required','current_password']]);
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();

        return back()->with('status', '2FA desactivado.');
    }
}