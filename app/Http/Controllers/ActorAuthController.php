<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ActorAuthController extends Controller
{
    private const ACTORS = [
        'souscripteur' => [
            'credential' => 'code',
            'login_view' => 'souscripteur.login',
            'dashboard_view' => 'souscripteur.dashboard',
            'dashboard_route' => 'souscripteur.dashboard',
            'login_route' => 'souscripteur.login',
        ],
        'responsable' => [
            'credential' => 'email',
            'login_view' => 'responsable.login',
            'dashboard_view' => 'responsable.dashboard',
            'dashboard_route' => 'responsable.dashboard',
            'login_route' => 'responsable.login',
        ],
        'agent' => [
            'credential' => 'email',
            'login_view' => 'agent.login',
            'dashboard_view' => 'agent.dashboard',
            'dashboard_route' => 'agent.dashboard',
            'login_route' => 'agent.login',
        ],
        'admin' => [
            'credential' => 'email',
            'login_view' => 'admin.login',
            'dashboard_view' => 'admin.dashboard',
            'dashboard_route' => 'admin.dashboard',
            'login_route' => 'admin.login',
        ],
    ];

    public function showLogin(string $actor): View
    {
        return view($this->actorConfig($actor)['login_view']);
    }

    public function login(Request $request, string $actor): RedirectResponse
    {
        $config = $this->actorConfig($actor);
        $credential = $config['credential'];

        $validated = $request->validate([
            $credential => $credential === 'email' ? ['required', 'email'] : ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request, $actor, $credential);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                $credential => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (! Auth::guard($actor)->attempt($validated)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                $credential => 'Les identifiants fournis sont incorrects.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return redirect()->route($config['dashboard_route']);
    }

    public function dashboard(string $actor): View
    {
        return view($this->actorConfig($actor)['dashboard_view'], [
            'actor' => Auth::guard($actor)->user(),
        ]);
    }

    public function souscripteurProfile(): View
    {
        return view('souscripteur.profil', [
            'souscripteur' => Auth::guard('souscripteur')->user(),
        ]);
    }

    public function logout(Request $request, string $actor): RedirectResponse
    {
        Auth::guard($actor)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($this->actorConfig($actor)['login_route']);
    }

    private function actorConfig(string $actor): array
    {
        abort_unless(isset(self::ACTORS[$actor]), 404);

        return self::ACTORS[$actor];
    }

    private function throttleKey(Request $request, string $actor, string $credential): string
    {
        return Str::lower($actor.'|'.$request->input($credential, '').'|'.$request->ip());
    }
}
