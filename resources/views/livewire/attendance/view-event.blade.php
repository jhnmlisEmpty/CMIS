<div class="event-page event-detail-page">
    <x-slot:headerTitle>Event Details</x-slot:headerTitle>

    <x-page-header
        :title="$event->title"
        subtitle="Manage event details, live check-ins, and attendance activity."
        :backRoute="route('events.index')"
        backLabel="Events">
        <x-slot:actions>
            <a href="{{ route('events.update', $event->id) }}" class="event-button-secondary" wire:navigate>
                <x-heroicon-o-pencil-square aria-hidden="true" />
                Edit event
            </a>
        </x-slot:actions>
    </x-page-header>

    <input type="hidden" wire:model="scannedUuid" @keydown.enter="$wire.handleQrScan()" id="qr-input">

    @if($message)
        <div @class(['event-alert', 'event-alert-success' => $messageType === 'success', 'event-alert-error' => $messageType !== 'success']) role="status">
            @if($messageType === 'success')
                <x-heroicon-o-check aria-hidden="true" />
            @else
                <x-heroicon-o-exclamation-triangle aria-hidden="true" />
            @endif
            <span>{{ $message }}</span>
        </div>
    @endif

    @php
        $eventDate = \Illuminate\Support\Carbon::parse($event->event_date);
        $attendances = $this->getAttendances();
        $stats = $this->getAttendanceStats();
    @endphp

    <section class="event-detail-hero" aria-label="Event overview">
        <div class="event-detail-date" aria-hidden="true">
            <span>{{ $eventDate->format('M') }}</span>
            <strong>{{ $eventDate->format('d') }}</strong>
            <small>{{ $eventDate->format('Y') }}</small>
        </div>
        <div class="event-detail-copy">
            <span class="event-type-badge">{{ ucfirst($event->event_type) }}</span>
            <p>{{ $eventDate->format('l, F j, Y') }}</p>
            <div>
                <x-heroicon-o-map-pin aria-hidden="true" />
                <span>{{ $event->location }}</span>
            </div>
        </div>
        <div class="event-detail-count">
            <span>Checked in</span>
            <strong>{{ $attendances->count() }}</strong>
            <small>{{ Str::plural('member', $attendances->count()) }}</small>
        </div>
    </section>

    @if($event->description)
        <section class="event-description-panel" aria-labelledby="event-description-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">01</span><h2 id="event-description-title">About this event</h2></div>
            </div>
            <p>{{ $event->description }}</p>
        </section>
    @endif

    <div class="event-detail-grid">
        <section class="event-checkin-panel" aria-labelledby="checkin-title">
            <div class="event-section-heading">
                <div>
                    <span class="event-section-index">01</span>
                    <h2 id="checkin-title">Member check-in</h2>
                </div>
                <span class="event-live-indicator"><i></i> Ready to scan</span>
            </div>

            <div class="event-checkin-methods">
                <div class="event-checkin-method">
                    <span class="event-method-icon">
                        <x-heroicon-o-qr-code aria-hidden="true" />
                    </span>
                    <div class="event-method-copy">
                        <label for="qr-scanner">Scan a member QR code</label>
                        <p>Keep this field active, scan the code, then press Enter.</p>
                    </div>
                    <input type="text" id="qr-scanner" wire:model="scannedUuid" @keydown.enter="$wire.handleQrScan()" placeholder="Waiting for QR code…" autocomplete="off" autofocus>
                </div>

                <div class="event-method-divider"><span>or</span></div>

                <div class="event-checkin-method">
                    <span class="event-method-icon">
                        <x-heroicon-o-magnifying-glass aria-hidden="true" />
                    </span>
                    <div class="event-method-copy">
                        <label for="name-search">Find a member by name</label>
                        <p>Choose a result to check the member in immediately.</p>
                    </div>
                    <div class="event-member-search">
                        <input type="search" id="name-search" wire:model.live.debounce.250ms="searchName" placeholder="Start typing a name…" autocomplete="off">
                        @if($this->getSearchResults()->count() > 0)
                            <div class="event-search-results" role="listbox">
                                @foreach($this->getSearchResults() as $user)
                                    <button type="button" wire:click="checkInByUserId({{ $user->id }})" role="option">
                                        <span class="event-member-initials">{{ collect(explode(' ', $user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                                        <span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span>
                                        <x-heroicon-o-plus aria-hidden="true" />
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen(trim($searchName)) >= 2)
                            <div class="event-search-results event-search-no-results">No active members found.</div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <aside class="event-quick-stats" aria-labelledby="quick-stats-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">02</span><h2 id="quick-stats-title">Today at a glance</h2></div>
            </div>
            <dl>
                <div><dt>Total check-ins</dt><dd>{{ $stats['total'] }}</dd></div>
                <div><dt>First arrival</dt><dd>{{ $stats['earliest'] ?? '—' }}</dd></div>
                <div><dt>Latest arrival</dt><dd>{{ $stats['latest'] ?? '—' }}</dd></div>
                <div><dt>Peak hour</dt><dd>{{ $stats['peakHour'] ?? '—' }}</dd></div>
            </dl>
        </aside>
    </div>

    @if($attendances->count() > 0)
        <section class="event-analytics-panel" aria-labelledby="attendance-trend-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">03</span><h2 id="attendance-trend-title">Attendance trend</h2></div>
                <p>Check-ins by minute</p>
            </div>
            <div class="event-chart-wrap">
                <canvas
                    wire:ignore
                    id="attendanceChart"
                    data-attendance-chart="{{ base64_encode(json_encode($this->getAttendanceChartData())) }}"
                ></canvas>
            </div>
        </section>
    @endif

    <section class="event-attendance-panel" aria-labelledby="checked-in-title">
        <div class="event-section-heading">
            <div><span class="event-section-index">{{ $attendances->count() > 0 ? '04' : '03' }}</span><h2 id="checked-in-title">Checked-in members</h2></div>
            <p>{{ $attendances->count() }} {{ Str::plural('record', $attendances->count()) }}</p>
        </div>

        @if($attendances->count() > 0)
            <div class="event-attendance-table-wrap">
                <table class="event-attendance-table">
                    <thead><tr><th>Member</th><th>Small group</th><th>Phone</th><th>Check-in</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach($attendances as $attendance)
                            <tr>
                                <td>
                                    <div class="event-member-cell">
                                        <span class="event-member-initials">{{ collect(explode(' ', $attendance->user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                                        <div><strong>{{ $attendance->user->name }}</strong><small>{{ $attendance->user->email }}</small></div>
                                    </div>
                                </td>
                                <td>@if($attendance->user->smallGroups->count() > 0)<span class="event-type-badge">{{ $attendance->user->smallGroups->first()->name }}</span>@else<span class="event-muted">No group</span>@endif</td>
                                <td>{{ $attendance->user->phone ?: '—' }}</td>
                                <td><time datetime="{{ $attendance->check_in_time->toIso8601String() }}"><strong>{{ $attendance->check_in_time->format('M j, Y') }}</strong><small>{{ $attendance->check_in_time->format('g:i A') }}</small></time></td>
                                <td><a href="{{ route('users.show', $attendance->user->id) }}" class="event-icon-button" wire:navigate title="View member" aria-label="View {{ $attendance->user->name }}"><x-heroicon-o-chevron-right /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="event-empty-state event-empty-compact">
                <span class="event-empty-icon"><x-heroicon-o-user-plus aria-hidden="true" /></span>
                <h3>No one has checked in yet</h3>
                <p>Scan a QR code or search for a member above to record the first arrival.</p>
            </div>
        @endif
    </section>
</div>
