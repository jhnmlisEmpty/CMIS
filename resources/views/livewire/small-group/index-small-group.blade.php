<div class="event-page group-page">
    <x-slot:headerTitle>Small Groups</x-slot:headerTitle>
    <x-slot:headerSubtitle>{{ $churchSettings->name }}</x-slot:headerSubtitle>

    <x-page-header title="Small groups" subtitle="Build communities, organize members, and guide lessons in one place.">
        <x-slot:actions>
            @can('small_groups.create')<a href="{{ route('small-groups.create') }}" class="event-button-primary" wire:navigate>
                <x-heroicon-o-plus aria-hidden="true" />
                New small group
            </a>@endcan
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status">
            <x-heroicon-o-check aria-hidden="true" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <section class="event-directory group-directory" aria-labelledby="group-directory-title">
        <div class="event-directory-toolbar">
            <div>
                <span class="event-eyebrow">Group directory</span>
                <h2 id="group-directory-title">{{ $smallGroups->total() }} {{ Str::plural('community', $smallGroups->total()) }}</h2>
            </div>
            <div class="event-filter-group">
                <label class="event-search" for="group-search">
                    <x-heroicon-o-magnifying-glass aria-hidden="true" />
                    <span class="sr-only">Search small groups</span>
                    <input id="group-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search group, leader, or tag">
                </label>
                <label class="event-type-filter" for="group-tag-filter">
                    <span class="sr-only">Filter by tag</span>
                    <select id="group-tag-filter" wire:model.live="tagFilter">
                        <option value="">All tags</option>
                        @foreach($tags as $tag)<option value="{{ $tag->id }}">{{ $tag->name }}</option>@endforeach
                    </select>
                    <x-heroicon-o-chevron-down aria-hidden="true" />
                </label>
                <label class="event-type-filter" for="group-status-filter">
                    <span class="sr-only">Filter by status</span>
                    <select id="group-status-filter" wire:model.live="statusFilter">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach
                    </select>
                    <x-heroicon-o-chevron-down aria-hidden="true" />
                </label>
            </div>
        </div>

        @if($search || $statusFilter || $tagFilter)
            <div class="event-active-filter"><p>Showing groups matching your filters</p><button wire:click="clearFilters">Clear filters</button></div>
        @endif

        <div class="event-list-head">
            <button wire:click="sort('name')">Group @if($sortBy === 'name')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</button>
            <span>Leaders</span><span>Members</span><span>Tags</span><span>Status</span><span><span class="sr-only">Actions</span></span>
        </div>

        <div class="event-list" wire:loading.class="is-loading" wire:target="search,statusFilter,sort,clearFilters">
            @forelse($smallGroups as $group)
                <article class="event-row group-row">
                    <a href="{{ route('small-groups.show', $group) }}" class="event-title-cell" wire:navigate>
                        @if($group->photo_path)<img src="{{ route('small-group-photo', ['filename' => basename($group->photo_path)]) }}" alt="{{ $group->name }}" class="group-mark group-photo">@else<span class="group-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($group->name, 0, 2)) }}</span>@endif
                        <span><strong>{{ $group->name }}</strong><small>{{ $group->description ?: 'No description added' }}</small></span>
                    </a>
                    <div class="group-leader-cell">
                        <span><strong>{{ $group->leaders->pluck('name')->join(', ') ?: 'No leaders' }}</strong><small>{{ Str::plural('Group leader', $group->leaders->count()) }}</small></span>
                    </div>
                    <div class="group-count-cell">
                        <strong>{{ $group->members->count() }}</strong><span>{{ Str::plural('member', $group->members->count()) }}</span>
                    </div>
                    <div class="record-tag-cell">@forelse($group->tags as $tag)<span class="group-tag-badge">{{ $tag->name }}</span>@empty<span class="group-tag-badge is-empty">No tags</span>@endforelse</div>
                    <div><span @class(['group-status', 'is-active' => $group->status === 'active'])><i></i>{{ ucfirst($group->status) }}</span></div>
                    <div class="event-row-actions">
                        <a href="{{ route('small-groups.show', $group) }}" title="Open group" aria-label="Open {{ $group->name }}" wire:navigate><x-heroicon-o-chevron-right /></a>
                        @if(auth()->user()->canAccessSmallGroup($group, 'small_groups.update'))<a href="{{ route('small-groups.edit', $group) }}" title="Edit group" aria-label="Edit {{ $group->name }}" wire:navigate><x-heroicon-o-pencil-square /></a>@endif
                        @if(auth()->user()->canAccessSmallGroup($group, 'small_groups.delete'))<button wire:click="deleteSmallGroup({{ $group->id }})" wire:confirm="Delete {{ $group->name }}? This cannot be undone." title="Delete group" aria-label="Delete {{ $group->name }}"><x-heroicon-o-trash /></button>@endif
                    </div>
                </article>
            @empty
                <div class="event-empty-state">
                    <span class="event-empty-icon"><x-heroicon-o-user-group aria-hidden="true" /></span>
                    <h3>{{ $search || $statusFilter || $tagFilter ? 'No matching groups' : 'Start a small-group community' }}</h3>
                    <p>{{ $search || $statusFilter || $tagFilter ? 'Try another search or clear the filters to see every group.' : 'Create the first group, assign its leaders, then begin adding members and lessons.' }}</p>
                    @if($search || $statusFilter || $tagFilter)<button wire:click="clearFilters" class="event-button-secondary">Clear filters</button>@else @can('small_groups.create')<a href="{{ route('small-groups.create') }}" class="event-button-primary" wire:navigate>Create a group</a>@endcan @endif
                </div>
            @endforelse
        </div>

        @if($smallGroups->hasPages())<div class="event-pagination">{{ $smallGroups->links() }}</div>@endif
    </section>
</div>
