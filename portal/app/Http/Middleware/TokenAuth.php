<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TokenAuth
{
    private const COOKIE_NAME = 'zmc_auth';
    private const COOKIE_LIFETIME = 480; // 8 hours in minutes

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            return $next($request);
        }

        $cookie = $request->cookie(self::COOKIE_NAME);
        if (!$cookie) {
            return $next($request);
        }

        $payload = self::verifyToken($cookie);
        if (!$payload) {
            return $next($request);
        }

        $user = User::find($payload['uid']);
        if (!$user) {
            return $next($request);
        }

        if ($user->account_status === 'pending_verification') {
            return $next($request);
        }

        Auth::login($user);

        if ($request->hasSession() && $request->session()->isStarted()) {
            if (!empty($payload['role'])) {
                $request->session()->put('active_staff_role', $payload['role']);
            }
        }

        return $next($request);
    }

    public static function makeAuthCookie(User $user, string $role = ''): \Symfony\Component\HttpFoundation\Cookie
    {
        $expires = time() + (self::COOKIE_LIFETIME * 60);
        $payload = [
            'uid' => $user->id,
            'role' => $role,
            'exp' => $expires,
        ];
        $json = json_encode($payload);
        $sig = hash_hmac('sha256', $json, config('app.key'));
        $token = base64_encode($json . '.' . $sig);

        return cookie(
            self::COOKIE_NAME,
            $token,
            self::COOKIE_LIFETIME,
            '/',
            null,
            config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax'),
            config('session.partitioned', false)
        );
    }

    public static function forgetAuthCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        return cookie()->forget(self::COOKIE_NAME);
    }

    public static function verifyToken(string $token): ?array
    {
        $decoded = base64_decode($token, true);
        if (!$decoded) {
            return null;
        }

        $lastDot = strrpos($decoded, '.');
        if ($lastDot === false) {
            return null;
        }

        $json = substr($decoded, 0, $lastDot);
        $sig = substr($decoded, $lastDot + 1);

        $expected = hash_hmac('sha256', $json, config('app.key'));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['uid']) || !isset($payload['exp'])) {
            return null;
        }

        if ($payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }
}
