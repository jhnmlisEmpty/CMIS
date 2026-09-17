<x-layouts.app>
    <x-slot:headerTitle>Dashboard</x-slot:headerTitle>
    <x-slot:headerSubtitle>True Vine World Harvest Church - Pangasinan</x-slot:headerSubtitle>

    @php
        $hasUsers = \Illuminate\Support\Facades\Schema::hasTable('users');
        $hasEvents = \Illuminate\Support\Facades\Schema::hasTable('events');
        $hasGroups = \Illuminate\Support\Facades\Schema::hasTable('small_groups');
        $canViewUsers = Gate::allows('users.view');
        $canViewEvents = Gate::allows('events.view');
        $canViewGroups = Gate::allows('small_groups.view');
        $memberCount = $hasUsers && $canViewUsers ? \App\Models\User::query()->visibleTo(auth()->user())->count() : 0;
        $activeMemberCount = $hasUsers && $canViewUsers ? \App\Models\User::query()->visibleTo(auth()->user())->where('status', 'active')->count() : 0;
        $upcomingEventCount = $hasEvents && $canViewEvents ? \App\Models\Event::whereDate('event_date', '>=', today())->count() : 0;
        $groupCount = $hasGroups && $canViewGroups ? \App\Models\SmallGroup::query()->visibleTo(auth()->user())->count() : 0;
        $currentUser = auth()->user();
        $calendarMonth = now()->startOfMonth();
        $calendarEvents = $hasEvents && $canViewEvents
            ? \App\Models\Event::orderBy('event_date')->get(['id', 'title', 'event_date'])
            : collect();
        $calendarBirthdays = $hasUsers && $canViewUsers
            ? \App\Models\User::query()->visibleTo(auth()->user())->whereNotNull('birthdate')->orderBy('name')->get(['id', 'name', 'birthdate'])
            : collect();
    @endphp

    <div class="event-page home-page">
        <x-page-header title="Dashboard" subtitle="A clear view of your church community today." />

        <section class="home-hero" aria-labelledby="home-welcome-title">
            <div class="home-hero-copy">
                <p class="event-eyebrow">Community overview</p>
                <h2 id="home-welcome-title">Welcome back, {{ $currentUser?->name ?? 'True Vine team' }}.</h2>
                <p>Keep people connected, prepare upcoming gatherings, and support every small group from one shared workspace.</p>
                <div class="home-hero-actions">
                    @can('events.create')<a href="{{ route('events.create') }}" class="event-button-primary" wire:navigate><x-heroicon-o-plus />Create event</a>@endcan
                    @if(Gate::allows('users.create') && ! auth()->user()->isSmallGroupLeader())<a href="{{ route('users.create') }}" class="home-hero-secondary" wire:navigate>Add member</a>@endif
                </div>
            </div>
            <div class="home-hero-note"><span>{{ now()->format('l') }}</span><strong>{{ now()->format('d') }}</strong><small>{{ now()->format('F Y') }}</small></div>
        </section>

        <section class="home-metrics" aria-label="Church statistics">
            @can('users.view')<a href="{{ route('users.index') }}" class="home-metric" wire:navigate><span class="home-metric-index">01</span><strong>{{ number_format($memberCount) }}</strong><div><b>Total members</b><small>{{ number_format($activeMemberCount) }} currently active</small></div><x-heroicon-o-user-group /></a>@endcan
            @can('events.view')<a href="{{ route('events.index') }}" class="home-metric" wire:navigate><span class="home-metric-index">02</span><strong>{{ number_format($upcomingEventCount) }}</strong><div><b>Upcoming events</b><small>Gatherings on the calendar</small></div><x-heroicon-o-calendar-days /></a>@endcan
            @can('small_groups.view')<a href="{{ route('small-groups.index') }}" class="home-metric" wire:navigate><span class="home-metric-index">03</span><strong>{{ number_format($groupCount) }}</strong><div><b>Small groups</b><small>Communities growing together</small></div><x-heroicon-o-user-group /></a>@endcan
        </section>

        <section class="home-tools-grid" aria-label="Community tools">
            @if($canViewEvents || $canViewUsers)<section
                class="home-dashboard-panel home-calendar-panel"
                aria-labelledby="dashboard-calendar-title"
                x-data="dashboardCalendar(@js($calendarEvents->map(fn ($event) => ['id' => $event->id, 'title' => $event->title, 'date' => $event->event_date->format('Y-m-d'), 'url' => Gate::allows('attendance.view') ? route('events.view', $event) : route('events.index')])), @js($calendarBirthdays->map(fn ($member) => ['id' => $member->id, 'name' => $member->name, 'date' => $member->birthdate->format('m-d'), 'url' => route('users.show', $member)])), {{ $calendarMonth->year }}, {{ $calendarMonth->month - 1 }})"
            >
                <div class="home-panel-heading">
                    <div><span class="event-eyebrow">Planning</span><h3 id="dashboard-calendar-title" x-text="monthLabel"></h3></div>
                    <div class="home-calendar-controls">
                        <button type="button" @click="previousMonth" aria-label="Previous month">&larr;</button>
                        <button type="button" @click="goToToday">Today</button>
                        <button type="button" @click="nextMonth" aria-label="Next month">&rarr;</button>
                    </div>
                    @can('events.view')<a href="{{ route('events.index') }}" class="group-section-link" wire:navigate>View events</a>@endcan
                </div>
                <div class="home-calendar-weekdays">@foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)<span>{{ $day }}</span>@endforeach</div>
                <div class="home-calendar-grid">
                    <template x-for="day in days" :key="day.key">
                        <div class="home-calendar-day" :class="{ 'is-muted': !day.inMonth, 'is-today': day.isToday }">
                            <span x-text="day.number"></span>
                            <div class="home-calendar-events" x-show="day.items.length">
                                <template x-for="item in day.items.slice(0, 2)" :key="item.key">
                                    <a :href="item.url" :class="item.type" :title="item.label" x-text="item.label"></a>
                                </template>
                                <small x-show="day.items.length > 2" x-text="`+${day.items.length - 2} more`"></small>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="home-calendar-legend"><span class="event-dot"></span> Event <span class="birthday-dot"></span> Birthday</div>
            </section>@endif
            @can('users.map')<section class="home-dashboard-panel home-map-panel" aria-labelledby="dashboard-map-title">
                <div class="home-panel-heading">
                    <div><span class="event-eyebrow">Community</span><h3 id="dashboard-map-title">Member locations</h3></div>
                    <a href="{{ route('users.map') }}" class="group-section-link" wire:navigate>Open map</a>
                </div>
                <livewire:components.users-map :show-filters="false" :show-legend="false" :show-member-list="false" height="280px" status-filter="" />
            </section>@endcan
        </section>

    </div>
</x-layouts.app>
