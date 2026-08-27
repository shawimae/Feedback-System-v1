<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLicenseIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isDev()) {
            return $next($request);
        }

        if ($request->routeIs('logout')) {
            return $next($request);
        }

        $licenseState = AppSetting::licenseStateForUser($user);

        if ($licenseState['is_valid']) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('license_prompt', true)
            ->with('license_reason', $licenseState['reason'])
            ->with('license_can_request_renewal', $user->isSuperAdmin())
            ->withInput(['email' => $user->email])
            ->withErrors(['email' => $licenseState['message']]);
    }
}
