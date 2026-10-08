<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date', 'before:today'],
        ]);

        $name = preg_replace('/\s+/', ' ', trim($credentials['name']));
        $throttleKey = Str::transliterate(Str::lower($name).'|'.$request->ip());
        $ipThrottleKey = 'login-ip|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5) || RateLimiter::tooManyAttempts($ipThrottleKey, 20)) {
            $seconds = max(RateLimiter::availableIn($throttleKey), RateLimiter::availableIn($ipThrottleKey));

            throw ValidationException::withMessages([
                'name' => "Too many sign-in attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $matches = User::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->whereDate('birthdate', $credentials['birthdate'])
            ->where('status', User::STATUS_ACTIVE)
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            RateLimiter::hit($throttleKey, 60);
            RateLimiter::hit($ipThrottleKey, 300);
            $audit->log('login_failed', 'authentication', 'Failed sign-in attempt', null, [], ['name' => $name]);

            throw ValidationException::withMessages([
                'name' => 'We could not verify those member details.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        RateLimiter::clear($ipThrottleKey);
        $user = $matches->first();
        Auth::login($user, $request->boolean('remember') && ! $user->isAdmin());
        $request->session()->regenerate();
        $audit->log('login', 'authentication', $user->name.' signed in', $user, [], [], $user->id);

        $destination = $user->can('dashboard.view') ? route('home') : route('profile');

        return redirect()->intended($destination);
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $audit->log('logout', 'authentication', $user->name.' signed out', $user, [], [], $user->id);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
