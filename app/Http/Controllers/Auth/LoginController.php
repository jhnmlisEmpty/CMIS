<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date', 'before:today'],
        ]);

        $name = preg_replace('/\s+/', ' ', trim($credentials['name']));
        $throttleKey = Str::transliterate(Str::lower($name).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

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

            throw ValidationException::withMessages([
                'name' => 'We could not verify those member details.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($matches->first(), $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
