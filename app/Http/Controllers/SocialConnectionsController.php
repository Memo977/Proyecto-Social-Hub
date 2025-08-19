<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SocialConnectionsController extends Controller
{
    public function disconnect(Request $request, string $provider)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['mastodon','reddit'])) {
            abort(404);
        }

        $account = $request->user()->socialAccounts()->where('provider', $provider)->first();

        if ($account) {
            // Aquí podrías revocar token con tus Services si lo deseas
            $account->delete();
            return back()->with('status', "Se desconectó {$provider} correctamente.");
        }

        return back()->with('status', "No había cuenta de {$provider} conectada.");
    }
}