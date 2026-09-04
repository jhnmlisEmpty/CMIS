<div class="event-page member-page">
    <x-slot:headerTitle>Members</x-slot:headerTitle>

    <x-page-header title="Members" subtitle="Manage member profiles, contact information, and account access.">
        <x-slot:actions>
            <a href="{{ route('users.map') }}" class="event-button-secondary" wire:navigate><x-heroicon-o-map-pin aria-hidden="true" />Member map</a>
            <a href="{{ route('users.create') }}" class="event-button-primary" wire:navigate><x-heroicon-o-plus aria-hidden="true" />New member</a>
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>
    @endif

    <section class="event-directory member-directory" aria-labelledby="member-directory-title">
        <div class="event-directory-toolbar">
            <div><span class="event-eyebrow">Member directory</span><h2 id="member-directory-title">{{ $users->total() }} {{ Str::plural('person', $users->total()) }}</h2></div>
            <div class="member-filter-group">
                <label class="event-search" for="member-directory-search"><x-heroicon-o-magnifying-glass aria-hidden="true" /><span class="sr-only">Search members</span><input id="member-directory-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, email, or phone"></label>
                <label class="event-type-filter" for="member-role-filter"><span class="sr-only">Filter by role</span><select id="member-role-filter" wire:model.live="roleFilter"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>@endforeach</select><x-heroicon-o-chevron-down /></label>
                <label class="event-type-filter" for="member-status-filter"><span class="sr-only">Filter by status</span><select id="member-status-filter" wire:model.live="statusFilter"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select><x-heroicon-o-chevron-down /></label>
            </div>
        </div>

        @if($search || $roleFilter || $statusFilter)<div class="event-active-filter"><p>Showing members matching your filters</p><button wire:click="clearFilters">Clear filters</button></div>@endif

        <div class="event-list-head member-list-head">
            <button wire:click="sort('name')">Member @if($sortBy === 'name')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</button>
            <span>Contact</span><span>Location</span>
            <button wire:click="sort('role')">Role @if($sortBy === 'role')<span>{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>@endif</button>
            <span>Status</span><span><span class="sr-only">Actions</span></span>
        </div>

        <div class="event-list" wire:loading.class="is-loading" wire:target="search,roleFilter,statusFilter,sort,clearFilters">
            @forelse($users as $user)
                <article class="event-row member-row">
                    <a href="{{ route('users.show', $user) }}" class="event-title-cell" wire:navigate>
                        @if($user->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename($user->profile_photo_path)]) }}" alt="{{ $user->name }}" class="member-mark member-photo">@else<span class="member-mark" aria-hidden="true">{{ collect(explode(' ', $user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>@endif
                        <span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span>
                    </a>
                    <div class="member-contact-cell"><strong>{{ $user->phone ?: 'No phone' }}</strong><small>{{ $user->birthdate?->format('M j, Y') ?: 'No birthdate' }}</small></div>
                    <div class="event-location-cell"><x-heroicon-o-map-pin /><span title="{{ $user->address }}">{{ $user->address ?: 'No address' }}</span></div>
                    <div><span class="member-role-badge">{{ ucwords(str_replace('_', ' ', $user->role)) }}</span></div>
                    <div><span @class(['group-status', 'is-active' => $user->status === 'active'])><i></i>{{ ucfirst($user->status) }}</span></div>
                    <div class="event-row-actions">
                        <a href="{{ route('users.show', $user) }}" title="Open member" aria-label="Open {{ $user->name }}" wire:navigate><x-heroicon-o-chevron-right /></a>
                        <a href="{{ route('users.edit', $user) }}" title="Edit member" aria-label="Edit {{ $user->name }}" wire:navigate><x-heroicon-o-pencil-square /></a>
                        @if($user->id !== auth()->id())<button wire:click="deleteUser({{ $user->id }})" wire:confirm="Delete {{ $user->name }}? This cannot be undone." title="Delete member" aria-label="Delete {{ $user->name }}"><x-heroicon-o-trash /></button>@endif
                    </div>
                </article>
            @empty
                <div class="event-empty-state"><span class="event-empty-icon"><x-heroicon-o-user-plus /></span><h3>{{ $search || $roleFilter || $statusFilter ? 'No matching members' : 'Your member directory is ready' }}</h3><p>{{ $search || $roleFilter || $statusFilter ? 'Try another search or clear the filters to see every member.' : 'Add the first member to begin building the church directory.' }}</p>@if($search || $roleFilter || $statusFilter)<button wire:click="clearFilters" class="event-button-secondary">Clear filters</button>@else<a href="{{ route('users.create') }}" class="event-button-primary" wire:navigate>Add a member</a>@endif</div>
            @endforelse
        </div>
        @if($users->hasPages())<div class="event-pagination">{{ $users->links() }}</div>@endif
    </section>
</div>
