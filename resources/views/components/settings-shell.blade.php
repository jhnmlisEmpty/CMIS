@props(['active', 'title', 'description'])

<div class="event-page settings-page">
    <x-slot:headerTitle>Settings</x-slot:headerTitle>
    <x-slot:headerSubtitle>{{ $churchSettings->name }}</x-slot:headerSubtitle>

    <x-page-header :title="$title" :subtitle="$description" />

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>
    @endif

    <div class="settings-layout">
        <aside class="settings-rail">
            <span class="event-eyebrow">Administration</span>
            <nav aria-label="Settings sections">
                <a href="{{ route('settings.profile') }}" @class(['is-active' => $active === 'profile']) wire:navigate><x-heroicon-o-building-office-2 /><span><strong>Church profile</strong><small>Identity and regional preferences</small></span></a>
                <a href="{{ route('settings.attendance') }}" @class(['is-active' => $active === 'attendance']) wire:navigate><x-heroicon-o-clipboard-document-check /><span><strong>Attendance defaults</strong><small>Starting values for new events</small></span></a>
                <a href="{{ route('settings.tags') }}" @class(['is-active' => $active === 'tags']) wire:navigate><x-heroicon-o-tag /><span><strong>Tag management</strong><small>Rename, merge, and remove tags</small></span></a>
                <a href="{{ route('settings.access-control') }}" @class(['is-active' => $active === 'access']) wire:navigate><x-heroicon-o-shield-check /><span><strong>Roles &amp; access</strong><small>Create roles and manage permissions</small></span></a>
                <a href="{{ route('settings.audit-logs') }}" @class(['is-active' => $active === 'audit']) wire:navigate><x-heroicon-o-list-bullet /><span><strong>Audit logs</strong><small>Management and security history</small></span></a>
            </nav>
        </aside>
        <section class="settings-workspace">
            {{ $slot }}
        </section>
    </div>
</div>
