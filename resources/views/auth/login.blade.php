<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#173f35">
    <meta name="description" content="Sign in to the True Vine World Harvest Church - Pangasinan management system.">
    <link rel="icon" type="image/png" href="{{ asset('images/true-vine-logo.png') }}">
    <title>Sign in | True Vine World Harvest Church - Pangasinan</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body font-sans antialiased">
    <main class="login-shell">
        <section class="login-story" aria-labelledby="welcome-title">
            <div class="login-brand">
                <img src="{{ asset('images/true-vine-logo.png') }}" class="login-logo" alt="True Vine World Harvest Church - Pangasinan">
                <p class="login-kicker">True Vine World Harvest Church - Pangasinan</p>
            </div>
            <div class="login-message">
                <h1 id="welcome-title">People, gatherings, and care in one place.</h1>
                <p>Sign in to manage members, events, attendance, and small groups.</p>
            </div>
            <p class="login-footnote">Built for thoughtful community care.</p>
        </section>

        <section class="login-form-panel" aria-labelledby="sign-in-title">
            <div class="login-form-wrap">
                <div>
                    <h2 id="sign-in-title">Welcome back</h2>
                    <p>Use the name and birthdate saved in your member record.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <div>
                        <label for="name">Full name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus placeholder="Enter your full name" aria-describedby="name-error">
                        @error('name')<p id="name-error" class="login-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="birthdate">Birthdate</label>
                        <input id="birthdate" name="birthdate" type="date" value="{{ old('birthdate') }}" autocomplete="bday" max="{{ today()->subDay()->format('Y-m-d') }}" required aria-describedby="birthdate-error">
                        @error('birthdate')<p id="birthdate-error" class="login-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <label class="remember-control">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span>Keep me signed in on this device</span>
                    </label>
                    <button type="submit">Sign in</button>
                </form>

                <p class="login-help">Can’t sign in? Ask a church administrator to verify your member name and birthdate.</p>
            </div>
        </section>
    </main>
</body>
</html>
