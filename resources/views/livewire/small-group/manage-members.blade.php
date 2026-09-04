<div class="event-page group-page group-members-page">
    <x-slot:headerTitle>Manage Members</x-slot:headerTitle>

    <x-page-header
        :title="$smallGroup->name . ' members'"
        subtitle="Find available people, add them to the group, and maintain active membership."
        :backRoute="route('small-groups.show', $smallGroup)"
        backLabel="Group details" />

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="event-alert event-alert-error" role="alert"><x-heroicon-o-exclamation-triangle /><span>{{ session('error') }}</span></div>
    @endif

    <section class="group-workspace-summary" aria-label="Membership summary">
        <div><span class="group-mark">{{ mb_strtoupper(mb_substr($smallGroup->name, 0, 2)) }}</span><div><span class="event-eyebrow">Membership workspace</span><strong>{{ $smallGroup->members->count() }} {{ Str::plural('person', $smallGroup->members->count()) }} in this group</strong></div></div>
        <p>A member can belong to only one small group at a time.</p>
    </section>

    <div class="group-members-grid">
        <section class="event-checkin-panel" aria-labelledby="available-members-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">01</span><h2 id="available-members-title">Add a member</h2></div>
                <span class="group-result-count">{{ $availableUsers->count() }} available</span>
            </div>
            <div class="group-member-search-wrap">
                <label class="event-search" for="member-search">
                    <x-heroicon-o-magnifying-glass aria-hidden="true" />
                    <span class="sr-only">Search available members</span>
                    <input id="member-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search by name or email">
                </label>
            </div>

            @if($availableUsers->count() > 0)
                <ul class="group-member-list group-available-list">
                    @foreach($availableUsers as $user)
                        <li>
                            <span class="event-member-initials">{{ collect(explode(' ', $user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                            <div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div>
                            <button wire:click="addMember({{ $user->id }})" class="group-add-button" wire:loading.attr="disabled" wire:target="addMember({{ $user->id }})">
                                <x-heroicon-o-plus aria-hidden="true" /><span>Add</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="event-empty-state event-empty-compact">
                    <span class="event-empty-icon"><x-heroicon-o-magnifying-glass /></span>
                    <h3>{{ $search ? 'No available members found' : 'Everyone is already assigned' }}</h3>
                    <p>{{ $search ? 'Try searching with a different name or email address.' : 'All active members currently belong to a small group.' }}</p>
                </div>
            @endif
        </section>

        <section class="event-attendance-panel" aria-labelledby="current-members-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">02</span><h2 id="current-members-title">Current members</h2></div>
                <p>{{ $smallGroup->members->count() }} total</p>
            </div>
            @if($smallGroup->members->count() > 0)
                <ul class="group-member-list">
                    @foreach($smallGroup->members as $member)
                        <li>
                            <span class="event-member-initials">{{ collect(explode(' ', $member->user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                            <div><strong>{{ $member->user->name }}</strong><small>{{ $member->user->email }} · Joined {{ $member->joined_at?->format('M j, Y') ?? '—' }}</small></div>
                            <span @class(['group-status', 'is-active' => $member->status === 'active'])><i></i>{{ ucfirst($member->status) }}</span>
                            <div class="event-row-actions">
                                <button wire:click="toggleMemberStatus({{ $member->id }})" title="{{ $member->status === 'active' ? 'Deactivate member' : 'Activate member' }}" aria-label="{{ $member->status === 'active' ? 'Deactivate' : 'Activate' }} {{ $member->user->name }}">
                                    @if($member->status === 'active')<x-heroicon-o-check-circle />@else<x-heroicon-o-exclamation-circle />@endif
                                </button>
                                <button wire:click="removeMember({{ $member->id }})" wire:confirm="Remove {{ $member->user->name }} from this group?" title="Remove member" aria-label="Remove {{ $member->user->name }}"><x-heroicon-o-trash /></button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="event-empty-state event-empty-compact"><span class="event-empty-icon"><x-heroicon-o-user-plus /></span><h3>No members yet</h3><p>Use the search panel to add the first member.</p></div>
            @endif
        </section>
    </div>
</div>
