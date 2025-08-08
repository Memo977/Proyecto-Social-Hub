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

        return view('profile.two-factor', compact('user', 'secret', 'otpauth'));
    }

    public function enable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return back()->with('status', '2FA ya estaba activado.');
        }

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey(32);

        // Generar recovery codes pero NO mostrarlos hasta confirmación
        $codes = collect(range(1, 8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain' => $plain, 'hash' => Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();

        // NO mostrar códigos aquí, solo al confirmar
        return back()->with('status', 'Escanea el QR y confirma con un código OTP.');
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        abort_unless($user->two_factor_secret, 403);

        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid = app('pragmarx.google2fa')->verifyKey($secret, $request->input('code'));

        if (! $valid) {
            return back()->withErrors(['code' => 'Código inválido.']);
        }

        // REGENERAR códigos frescos para mostrar solo al confirmar por primera vez
        $codes = collect(range(1, 8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain' => $plain, 'hash' => Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
        ])->save();

        // Solo mostrar códigos en este momento crítico
        session()->flash('two_factor_plain_codes', $codes->pluck('plain')->values());

        return back()->with('status', '2FA activado. ¡Asegúrate de guardar tus códigos de recuperación!');
    }

    /**
     * VERSIÓN SEGURA: Regenerar recovery codes requiere verificación OTP
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'verification_code' => ['required', 'digits:6'], // Solo OTP de 6 dígitos
        ]);

        $user = $request->user();
        abort_unless($user->two_factor_enabled, 403);

        // Verificar que es un código OTP válido (NO recovery codes)
        $secret = Crypt::decryptString($user->two_factor_secret);
        $isValidOTP = app('pragmarx.google2fa')->verifyKey($secret, $request->input('verification_code'));

        if (!$isValidOTP) {
            return back()->withErrors([
                'verification_code' => 'Código OTP inválido. Solo se acepta código de 6 dígitos de tu authenticator.'
            ]);
        }

        // Generar nuevos recovery codes
        $codes = collect(range(1, 8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain' => $plain, 'hash' => Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
        ])->save();

        session()->flash('two_factor_plain_codes', $codes->pluck('plain')->values());

        return back()->with('status', 'Nuevos recovery codes generados.');
    }

    /**
     * VERSIÓN SEGURA: Desactivar 2FA requiere verificación OTP O recovery code
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'verification_code' => ['required', 'string', 'min:6'],
        ]);

        $user = $request->user();
        abort_unless($user->two_factor_enabled, 403);

        $verificationCode = $request->input('verification_code');
        $isValid = false;
        $codeType = null;

        // Opción 1: Verificar si es un código OTP de 6 dígitos
        if (preg_match('/^\d{6}$/', $verificationCode)) {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $isValid = app('pragmarx.google2fa')->verifyKey($secret, $verificationCode);
            if ($isValid) $codeType = 'otp';
        }

        // Opción 2: Si no es válido como OTP, verificar si es un recovery code
        if (!$isValid && $user->two_factor_recovery_codes) {
            $recoveryCodes = collect($user->two_factor_recovery_codes);
            
            foreach ($recoveryCodes as $index => $hashedCode) {
                if (Hash::check(strtoupper($verificationCode), $hashedCode)) {
                    $isValid = true;
                    $codeType = 'recovery';

                    // Remover el recovery code usado (un solo uso)
                    $recoveryCodes->forget($index);
                    $user->forceFill([
                        'two_factor_recovery_codes' => $recoveryCodes->values()
                    ])->save();

                    break;
                }
            }
        }

        if (!$isValid) {
            return back()->withErrors([
                'verification_code' => 'Código de verificación inválido. Usa un código OTP de tu authenticator o un recovery code.'
            ]);
        }

        // Si llegamos aquí, la verificación fue exitosa
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();

        $message = $codeType === 'recovery' 
            ? '2FA desactivado exitosamente usando recovery code.' 
            : '2FA desactivado exitosamente.';

        return back()->with('status', $message);
    }

    /**
     * MÉTODO AUXILIAR: Verificar códigos OTP
     */
    private function verifyOTPCode($user, $code)
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $secret = Crypt::decryptString($user->two_factor_secret);
        return app('pragmarx.google2fa')->verifyKey($secret, $code);
    }

    /**
     * MÉTODO AUXILIAR: Verificar recovery codes
     */
    private function verifyRecoveryCode($user, $code)
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        foreach ($user->two_factor_recovery_codes as $hashedCode) {
            if (Hash::check(strtoupper($code), $hashedCode)) {
                return true;
            }
        }

        return false;
    }
}