<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    /**
     * Muestra la vista para gestionar la autenticación de dos factores.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
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

    /**
     * Habilita la autenticación de dos factores para el usuario.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.current_password' => 'La contraseña proporcionada no coincide con la actual.',
        ]);

        $user = $request->user();

        if ($user->two_factor_enabled) {
            return back()->with('status', 'La autenticación de dos factores ya está habilitada.');
        }

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey(32);

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

        return back()->with('status', 'Escanea el código QR y confirma con un código OTP.');
    }

    /**
     * Confirma la activación de la autenticación de dos factores.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'El código OTP es obligatorio.',
            'code.digits' => 'El código OTP debe tener exactamente 6 dígitos.',
        ]);

        $user = $request->user();

        abort_unless($user->two_factor_secret, 403, 'No se ha configurado un secreto para la autenticación de dos factores.');

        $secret = Crypt::decryptString($user->two_factor_secret);
        $valid = app('pragmarx.google2fa')->verifyKey($secret, $request->input('code'));

        if (!$valid) {
            return back()->withErrors(['code' => 'El código OTP proporcionado es inválido.']);
        }

        $codes = collect(range(1, 8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain' => $plain, 'hash' => Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
        ])->save();

        session()->flash('two_factor_plain_codes', $codes->pluck('plain')->values());

        return back()->with('status', 'Autenticación de dos factores habilitada. Asegúrate de guardar tus códigos de recuperación.');
    }

    /**
     * Regenera los códigos de recuperación, requiriendo verificación OTP.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'verification_code' => ['required', 'digits:6'],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.current_password' => 'La contraseña proporcionada no coincide con la actual.',
            'verification_code.required' => 'El código OTP es obligatorio.',
            'verification_code.digits' => 'El código OTP debe tener exactamente 6 dígitos.',
        ]);

        $user = $request->user();
        abort_unless($user->two_factor_enabled, 403, 'La autenticación de dos factores no está habilitada.');

        $secret = Crypt::decryptString($user->two_factor_secret);
        $isValidOTP = app('pragmarx.google2fa')->verifyKey($secret, $request->input('verification_code'));

        if (!$isValidOTP) {
            return back()->withErrors([
                'verification_code' => 'El código OTP proporcionado es inválido. Usa el código de 6 dígitos de tu aplicación de autenticación.',
            ]);
        }

        $codes = collect(range(1, 8))->map(function () {
            $plain = Str::upper(Str::random(10));
            return ['plain' => $plain, 'hash' => Hash::make($plain)];
        });

        $user->forceFill([
            'two_factor_recovery_codes' => $codes->pluck('hash')->values(),
        ])->save();

        session()->flash('two_factor_plain_codes', $codes->pluck('plain')->values());

        return back()->with('status', 'Nuevos códigos de recuperación generados.');
    }

    /**
     * Deshabilita la autenticación de dos factores, requiriendo OTP o código de recuperación.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'verification_code' => ['required', 'string', 'min:6'],
        ], [
            'password.required' => 'La contraseña es obligatoria.',
            'password.current_password' => 'La contraseña proporcionada no coincide con la actual.',
            'verification_code.required' => 'El código de verificación es obligatorio.',
            'verification_code.min' => 'El código de verificación debe tener al menos 6 caracteres.',
        ]);

        $user = $request->user();
        abort_unless($user->two_factor_enabled, 403, 'La autenticación de dos factores no está habilitada.');

        $verificationCode = $request->input('verification_code');
        $isValid = false;
        $codeType = null;

        if (preg_match('/^\d{6}$/', $verificationCode)) {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $isValid = app('pragmarx.google2fa')->verifyKey($secret, $verificationCode);
            if ($isValid) $codeType = 'otp';
        }

        if (!$isValid && $user->two_factor_recovery_codes) {
            $recoveryCodes = collect($user->two_factor_recovery_codes);

            foreach ($recoveryCodes as $index => $hashedCode) {
                if (Hash::check(strtoupper($verificationCode), $hashedCode)) {
                    $isValid = true;
                    $codeType = 'recovery';

                    $recoveryCodes->forget($index);
                    $user->forceFill([
                        'two_factor_recovery_codes' => $recoveryCodes->values(),
                    ])->save();

                    break;
                }
            }
        }

        if (!$isValid) {
            return back()->withErrors([
                'verification_code' => 'El código de verificación es inválido. Usa un código OTP de tu aplicación de autenticación o un código de recuperación.',
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_enabled' => false,
        ])->save();

        $message = $codeType === 'recovery'
            ? 'Autenticación de dos factores desactivada usando un código de recuperación.'
            : 'Autenticación de dos factores desactivada correctamente.';

        return back()->with('status', $message);
    }

    /**
     * Verifica un código OTP.
     *
     * @param mixed $user
     * @param string $code
     * @return bool
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
     * Verifica un código de recuperación.
     *
     * @param mixed $user
     * @param string $code
     * @return bool
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