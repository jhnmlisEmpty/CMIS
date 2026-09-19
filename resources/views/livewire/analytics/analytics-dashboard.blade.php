<div class="event-page analytics-page">
    <x-slot:headerTitle>Analytics</x-slot:headerTitle>
    <x-slot:headerSubtitle>Attendance, growth, and cell-group health</x-slot:headerSubtitle>

    <x-page-header title="Church analytics" subtitle="Use finalized required attendance and lesson progress to identify trends and people who may need support." />

    @if(session('success'))<div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>@endif

    <div class="analytics-toolbar">
        <nav aria-label="Analytics views">
            @foreach(['overview' => 'Overview', 'events' => 'Events', 'groups' => 'Cell groups', 'watchlist' => 'Watchlist'] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" @class(['is-active' => $tab === $key])>{{ $label }}</button>
            @endforeach
        </nav>
        @if($tab !== 'watchlist')<label>Reporting month<input type="month" wire:model.live="month"></label>@endif
    </div>

    @if($tab === 'overview')
        <section class="analytics-kpis" aria-label="{{ $monthLabel }} summary">
            <div><small>Total check-ins</small><strong>{{ number_format($metrics['checkins']) }}</strong><span>{{ $monthLabel }}</span></div>
            <div><small>Unique attendees</small><strong>{{ number_format($metrics['unique_attendees']) }}</strong><span>Across every event</span></div>
            <div><small>Required attendance</small><strong>{{ $metrics['rate'] === null ? 'No data' : $metrics['rate'].'%' }}</strong><span>{{ $metrics['present'] }} present, {{ $metrics['absent'] }} absent</span></div>
            <div><small>Members to reach</small><strong>{{ $watchlist->count() }}</strong><span>Current watchlist</span></div>
        </section>

        <section class="analytics-panel" aria-labelledby="monthly-trend-title">
            <header><div><span class="event-eyebrow">Last 12 months</span><h2 id="monthly-trend-title">Required attendance trend</h2></div><p>Percentage present among finalized required attendance records.</p></header>
            @if($trend->contains('has_data', true))<div class="analytics-bars">@foreach($trend as $point)<div title="{{ $point['label'] }}: {{ $point['has_data'] ? $point['rate'].'%' : 'No data' }}"><span><i style="height: {{ $point['has_data'] ? max(2, $point['rate']) : 0 }}%"></i></span><strong>{{ $point['has_data'] ? $point['rate'].'%' : '—' }}</strong><small>{{ Str::before($point['label'], ' ') }}</small></div>@endforeach</div>@else<div class="analytics-chart-empty"><x-heroicon-o-chart-bar /><h3>No finalized attendance trend yet</h3><p>The chart will appear after the first required event is finalized.</p></div>@endif
        </section>

        <div class="analytics-split">
            <section class="analytics-panel"><header><div><span class="event-eyebrow">Event health</span><h2>Events this month</h2></div><button wire:click="$set('tab', 'events')">View details</button></header>@include('livewire.analytics.partials.event-table', ['rows' => $eventRows->take(5), 'compact' => true])</section>
            <section class="analytics-panel"><header><div><span class="event-eyebrow">Care signal</span><h2>Priority watchlist</h2></div><button wire:click="$set('tab', 'watchlist')">View all</button></header>@include('livewire.analytics.partials.watchlist', ['rows' => $watchlist->take(5)])</section>
        </div>
    @elseif($tab === 'events')
        <section class="analytics-panel"><header><div><span class="event-eyebrow">{{ $monthLabel }}</span><h2>Event attendance</h2></div><p>Optional events show check-ins; required events also show a finalized rate.</p></header>@include('livewire.analytics.partials.event-table', ['rows' => $eventRows])</section>
    @elseif($tab === 'groups')
        <section class="analytics-panel"><header><div><span class="event-eyebrow">Group health</span><h2>Cell-group progress</h2></div><p>Attendance uses snapshotted group membership. Lessons use active members and published curriculum.</p></header>
            @if($groupRows->isNotEmpty())<div class="analytics-table-wrap"><table><thead><tr><th>Cell group</th><th>Members</th><th>Attendance</th><th>Lesson completion</th><th></th></tr></thead><tbody>@foreach($groupRows as $row)<tr><td><strong>{{ $row['model']->name }}</strong><small>{{ $row['model']->leader?->name ?? 'No leader' }}</small></td><td>{{ $row['members'] }}</td><td><span class="analytics-rate">{{ $row['attendance_rate'] === null ? 'No data' : $row['attendance_rate'].'%' }}</span></td><td><span class="analytics-rate">{{ $row['lesson_rate'] === null ? 'No data' : $row['lesson_rate'].'%' }}</span></td><td><a href="{{ route('small-groups.show', $row['model']) }}" wire:navigate aria-label="View {{ $row['model']->name }}"><x-heroicon-o-chevron-right /></a></td></tr>@endforeach</tbody></table></div>@else<div class="event-empty-state"><h3>No active cell groups</h3><p>Create a cell group to begin tracking progress.</p></div>@endif
        </section>
    @else
        <div class="analytics-watchlist-layout">
            <section class="analytics-panel"><header><div><span class="event-eyebrow">Member care</span><h2>Low-attendance watchlist</h2></div><label class="analytics-search"><x-heroicon-o-magnifying-glass /><input type="search" wire:model.live.debounce.300ms="search" placeholder="Search members"></label></header>@include('livewire.analytics.partials.watchlist', ['rows' => $watchlist])</section>
            @can('analytics.manage_settings')<aside class="analytics-settings"><h2>Watchlist rules</h2><p>A member appears when either threshold is reached.</p><form wire:submit="saveSettings"><label>Score below<input type="number" min="0" max="100" wire:model="lowScoreThreshold"></label><label>Attendance below (%)<input type="number" min="0" max="100" wire:model="lowAttendanceThreshold"></label><label>Rolling period (days)<input type="number" min="7" max="730" wire:model="rollingDays"></label><label>Minimum required events<input type="number" min="1" max="50" wire:model="minimumRequiredEvents"></label><button class="event-button-primary">Save rules</button></form></aside>@endcan
        </div>
    @endif
</div>
