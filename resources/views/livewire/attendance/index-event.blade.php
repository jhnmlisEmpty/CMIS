<div class="event-page">
    <x-slot:headerTitle>Events</x-slot:headerTitle>

    <x-page-header title="Events" subtitle="Plan gatherings and manage attendance from one place.">
        <x-slot:actions>
            <a href="{{ route('events.create') }}" class="event-button-primary" wire:navigate>
                <x-heroicon-o-plus aria-hidden="true" />
                New event
            </a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status">
            <x-heroicon-o-check aria-hidden="true" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <section class="event-directory" aria-labelledby="event-directory-title">
        <div class="event-directory-toolbar">
            <div>
                <span class="event-eyebrow">Event directory</span>
                <h2 id="event-directory-title">{{ $events->total() }} {{ Str::plural('gathering', $events->total()) }}</h2>
            </div>

            <div class="event-filter-group">
                <label class="event-search" for="event-search">
                    <x-heroicon-o-magnifying-glass aria-hidden="true" />
                    <span class="sr-only">Search events</span>
                    <input id="event-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search title or location">
                </label>
                <label class="event-type-filter" for="event-type-filter">
                    <span class="sr-only">Filter by event type</span>
                    <select id="event-type-filter" wire:model.live="typeFilter">
                        <option value="">All types</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                    <x-heroicon-o-chevron-down aria-hidden="true" />
                </label>
            </div>
        </div>

        @if($search || $typeFilter)
            <div class="event-active-filter">
                <p>Showing results matching your filters</p>
                <button wire:click="clearFilters">Clear filters</button>
            </div>
        @endif

        <div class="event-list-head">
            <button wire:click="sort('title')">Event @if($sortBy === 'title')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</button>
            <button wire:click="sort('event_date')">Date @if($sortBy === 'event_date')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</button>
            <span>Location</span>
            <button wire:click="sort('event_type')">Type @if($sortBy === 'event_type')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</button>
            <span><span class="sr-only">Actions</span></span>
        </div>

        <div class="event-list" wire:loading.class="is-loading" wire:target="search,typeFilter,sort,clearFilters">
            @forelse($events as $event)
                @php
                    $eventDate = \Illuminate\Support\Carbon::parse($event->event_date);
                @endphp
                <article class="event-row">
                    <a href="{{ route('events.view', $event->id) }}" class="event-title-cell" wire:navigate>
                        <span class="event-date-tile" aria-hidden="true">
                            <strong>{{ $eventDate->format('d') }}</strong>
                            <small>{{ $eventDate->format('M') }}</small>
                        </span>
                        <span>
                            <strong>{{ $event->title }}</strong>
                            <small>#{{ str_pad($event->id, 4, '0', STR_PAD_LEFT) }}</small>
                        </span>
                    </a>
                    <time datetime="{{ $eventDate->format('Y-m-d') }}">
                        <strong>{{ $eventDate->format('M j, Y') }}</strong>
                        <small>{{ $eventDate->isPast() ? 'Past event' : $eventDate->diffForHumans() }}</small>
                    </time>
                    <div class="event-location-cell">
                        <x-heroicon-o-map-pin aria-hidden="true" />
                        <span title="{{ $event->location }}">{{ $event->location }}</span>
                    </div>
                    <div><span class="event-type-badge">{{ ucfirst($event->event_type) }}</span></div>
                    <div class="event-row-actions">
                        <a href="{{ route('events.view', $event->id) }}" title="Open event" aria-label="Open {{ $event->title }}" wire:navigate>
                            <x-heroicon-o-chevron-right />
                        </a>
                        <a href="{{ route('events.update', $event->id) }}" title="Edit event" aria-label="Edit {{ $event->title }}" wire:navigate>
                            <x-heroicon-o-pencil-square />
                        </a>
                        <button wire:click="deleteEvent({{ $event->id }})" wire:confirm="Delete {{ $event->title }}? This cannot be undone." title="Delete event" aria-label="Delete {{ $event->title }}">
                            <x-heroicon-o-trash />
                        </button>
                    </div>
                </article>
            @empty
                <div class="event-empty-state">
                    <span class="event-empty-icon">
                        <x-heroicon-o-calendar-days aria-hidden="true" />
                    </span>
                    <h3>{{ $search || $typeFilter ? 'No matching events' : 'Your event calendar is ready' }}</h3>
                    <p>{{ $search || $typeFilter ? 'Try another search or clear the filters to see every event.' : 'Create the first event to start tracking gatherings and attendance.' }}</p>
                    @if($search || $typeFilter)
                        <button wire:click="clearFilters" class="event-button-secondary">Clear filters</button>
                    @else
                        <a href="{{ route('events.create') }}" class="event-button-primary" wire:navigate>Create an event</a>
                    @endif
                </div>
            @endforelse
        </div>

        @if($events->hasPages())
            <div class="event-pagination">{{ $events->links() }}</div>
        @endif
    </section>
</div>
