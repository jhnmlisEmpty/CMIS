<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#173f35">
    <meta name="description" content="True Vine World Harvest Church - Pangasinan management system for members, groups, events, and attendance.">
    <link rel="icon" type="image/png" href="{{ asset('images/true-vine-logo.png') }}">
    <title>{{ $title ?? (($headerTitle ?? null) ? $headerTitle . ' | True Vine World Harvest Church - Pangasinan' : 'True Vine World Harvest Church - Pangasinan') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="app-body min-h-screen font-sans antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <aside class="app-sidebar" aria-label="Primary navigation">
        <a href="{{ route('home') }}" class="brand-lockup" wire:navigate>
            <img src="{{ asset('images/true-vine-logo.png') }}" class="brand-logo" alt="True Vine World Harvest Church logo">
            <span><strong>TVWHC Pangasinan</strong><small>Church Management System</small></span>
        </a>
        <nav class="desktop-nav">
            <p class="nav-section-label">Workspace</p>
            <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'is-active' : '' }}" wire:navigate>
                <x-heroicon-o-squares-2x2 /><span>Overview</span>
            </a>
            <a href="{{ route('events.index') }}" class="nav-item {{ request()->routeIs('events.*') ? 'is-active' : '' }}" wire:navigate>
                <x-heroicon-o-calendar-days /><span>Events</span>
            </a>
            <a href="{{ route('small-groups.index') }}" class="nav-item {{ request()->routeIs('small-groups.*') ? 'is-active' : '' }}" wire:navigate>
                <x-heroicon-o-user-group /><span>Small groups</span>
            </a>
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'is-active' : '' }}" wire:navigate>
                <x-heroicon-o-user-group /><span>Members</span>
            </a>
            <p class="nav-section-label nav-section-label-secondary">Account</p>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button type="submit" class="nav-item logout-button">
                    <x-heroicon-o-arrow-left-start-on-rectangle /><span>Sign out</span>
                </button>
            </form>
        </nav>
    </aside>
    <header class="app-header">
        <div class="mobile-brand"><img src="{{ asset('images/true-vine-logo.png') }}" class="mobile-brand-logo" alt="True Vine World Harvest Church logo"><strong>True Vine World Harvest Church</strong></div>
        <div class="header-heading"><p>{{ $headerTitle ?? 'Overview' }}</p>@isset($headerSubtitle)<span>{{ $headerSubtitle }}</span>@endisset</div>
        <div class="header-actions">
            <a href="{{ route('users.create') }}" class="quick-add" wire:navigate><x-heroicon-o-plus /><span>Add member</span></a>
            <a href="{{ route('profile') }}" class="profile-link" aria-label="Open profile" wire:navigate>@if(auth()->user()?->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename(auth()->user()->profile_photo_path)]) }}" alt="{{ auth()->user()->name }}" class="header-profile-photo">@else<span>{{ auth()->check() ? mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) : 'CM' }}</span>@endif</a>
        </div>
    </header>
    <main id="main-content" class="app-main" tabindex="-1"><div class="page-content">{{ $slot }}</div></main>
    <nav class="mobile-nav" aria-label="Mobile navigation">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'is-active' : '' }}" wire:navigate><x-heroicon-o-squares-2x2 /><span>Overview</span></a>
        <a href="{{ route('events.index') }}" class="{{ request()->routeIs('events.*') ? 'is-active' : '' }}" wire:navigate><x-heroicon-o-calendar-days /><span>Events</span></a>
        <a href="{{ route('small-groups.index') }}" class="{{ request()->routeIs('small-groups.*') ? 'is-active' : '' }}" wire:navigate><x-heroicon-o-user-group /><span>Groups</span></a>
        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'is-active' : '' }}" wire:navigate><x-heroicon-o-user-group /><span>Members</span></a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-heroicon-o-arrow-left-start-on-rectangle /><span>Sign out</span></button></form>
    </nav>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
