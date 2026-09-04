<x-layouts.app>
    <x-slot:headerTitle>Profile</x-slot:headerTitle>
    @php $currentUser = auth()->user(); @endphp

    <div class="event-page profile-page">
        <x-page-header title="Profile" subtitle="Your account details and workspace access." />

        <section class="profile-hero" aria-labelledby="profile-name">
            <div class="profile-monogram">{{ $currentUser ? mb_strtoupper(mb_substr($currentUser->name, 0, 1)) : 'TV' }}</div>
            <div class="profile-hero-copy"><p class="event-eyebrow">Church account</p><h2 id="profile-name">{{ $currentUser?->name ?? 'True Vine Member' }}</h2><p>{{ $currentUser?->email ?? 'Local workspace account' }}</p></div>
            <span class="profile-access-badge">{{ $currentUser ? ucfirst($currentUser->status) : 'Local access' }}</span>
        </section>

        <div class="profile-content-grid">
            <section class="event-list profile-details" aria-labelledby="account-details-title">
                <div class="event-section-heading"><div><span>01</span><div><h3 id="account-details-title">Account details</h3><p>Your identity in this workspace.</p></div></div></div>
                <dl class="profile-detail-list">
                    <div><dt>Full name</dt><dd>{{ $currentUser?->name ?? 'True Vine Member' }}</dd></div>
                    <div><dt>Email address</dt><dd>{{ $currentUser?->email ?? 'Not provided' }}</dd></div>
                    <div><dt>Role</dt><dd>{{ $currentUser ? ucwords(str_replace('_', ' ', $currentUser->role)) : 'Guest' }}</dd></div>
                    <div><dt>Account status</dt><dd><span class="event-status-badge {{ ($currentUser?->status ?? null) === 'active' ? 'is-active' : 'is-neutral' }}">{{ $currentUser ? ucfirst($currentUser->status) : 'Local access' }}</span></dd></div>
                </dl>
            </section>

            <aside class="event-list profile-workspace" aria-labelledby="quick-access-title">
                <div class="event-section-heading"><div><span>02</span><div><h3 id="quick-access-title">Quick access</h3><p>Continue your day-to-day work.</p></div></div></div>
                <nav class="profile-links" aria-label="Profile quick access">
                    <a href="{{ route('users.index') }}" wire:navigate><span>Members</span><small>Browse the church directory</small><x-heroicon-o-chevron-right /></a>
                    <a href="{{ route('events.index') }}" wire:navigate><span>Events</span><small>Review upcoming gatherings</small><x-heroicon-o-chevron-right /></a>
                    <a href="{{ route('small-groups.index') }}" wire:navigate><span>Small groups</span><small>Manage growing communities</small><x-heroicon-o-chevron-right /></a>
                </nav>
            </aside>
        </div>
    </div>
</x-layouts.app>
