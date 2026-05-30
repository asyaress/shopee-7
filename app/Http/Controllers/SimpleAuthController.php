<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SimpleAuthController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('simple_auth') === true) {
            return redirect()->route('monitoring.index');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $expectedUser = (string) env('APP_TEST_USERNAME', 'admin');
        $expectedPass = (string) env('APP_TEST_PASSWORD', 'admin');

        $ok = hash_equals($expectedUser, (string) $request->input('username'))
            && hash_equals($expectedPass, (string) $request->input('password'));

        if (!$ok) {
            return back()->withErrors([
                'username' => 'Username/password salah.',
            ])->withInput($request->only('username'));
        }

        $request->session()->put('simple_auth', true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('simple_auth_intended');
        return redirect()->to($intended ?: route('monitoring.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('simple_auth');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
