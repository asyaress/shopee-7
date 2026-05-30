<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware login sederhana berbasis session.
 *
 * - Credential diambil dari .env (APP_TEST_USERNAME / APP_TEST_PASSWORD)
 * - Menyimpan flag session: simple_auth = true
 */
class SimpleAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authed = $request->session()->get('simple_auth') === true;

        if (!$authed) {
            // Simpan URL yang ingin diakses supaya setelah login balik lagi
            $request->session()->put('simple_auth_intended', $request->fullUrl());
            return redirect()->route('login');
        }

        return $next($request);
    }
}
