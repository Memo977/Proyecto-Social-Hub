<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request)
    {
        abort_unless($request->session()->has('login.id'), 403);
        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => ['nullable','string'],
            'recovery_code' => ['nullable','string'],
        ]);

        if (! $request->filled('code') && ! $request->filled('recovery_code')) {
            return back()->withErrors(['code' => 'Ingresa un código o un recovery code.']);
        }

        $id = $request->session()->pull('login.id');
        $user = User::findOrFail($id);

        if (! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return redirect()->route('login')->withErrors(['email'=>'2FA no está activo.']);
        }

        if ($request->filled('code')) {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $valid = app('pragmarx.google2fa')->verifyKey($secret, $request->input('code'));
            if (! $valid) {
                $request->session()->put('login.id', $id);
                return back()->withErrors(['code'=>'Código inválido.']);
            }
        }

        if ($request->filled('recovery_code')) {
            $codes = collect($user->two_factor_recovery_codes ?? []);
            $match = $codes->first(function ($hash) use ($request) {
                return Hash::check($request->input('recovery_code'), $hash);
            });

            if (! $match) {
                $request->session()->put('login.id', $id);
                return back()->withErrors(['recovery_code'=>'Recovery code inválido.']);
            }

            // invalidar el usado
            $codes = $codes->reject(function ($hash) use ($request) {
                return Hash::check($request->input('recovery_code'), $hash);
            })->values();

            $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}