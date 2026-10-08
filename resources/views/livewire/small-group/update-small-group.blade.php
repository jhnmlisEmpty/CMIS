<div class="event-page group-page">
    <x-slot:headerTitle>Edit Small Group</x-slot:headerTitle>
    <x-slot:headerSubtitle>{{ $churchSettings->name }}</x-slot:headerSubtitle>

    <x-page-header
        title="Edit small group"
        subtitle="Keep the group identity, leadership, and availability up to date."
        :backRoute="route('small-groups.show', $smallGroup)"
        backLabel="Group details" />

    <div class="event-editor-layout">
        <aside class="event-editor-intro" aria-label="Current group summary">
            <span class="event-eyebrow">Currently editing</span>
            @if($smallGroup->photo_path)<img src="{{ route('small-group-photo', ['filename' => basename($smallGroup->photo_path)]) }}" alt="{{ $smallGroup->name }}" class="group-editor-photo">@endif
            <h2>{{ $smallGroup->name }}</h2>
            <p>These changes appear throughout the member and lesson workspaces. Existing memberships and progress stay connected.</p>
            <dl class="event-editor-summary">
                <div><dt>Created</dt><dd>{{ $smallGroup->created_at?->format($churchSettings->date_format) ?? '—' }}</dd></div>
                <div><dt>Group ID</dt><dd>#{{ str_pad($smallGroup->id, 4, '0', STR_PAD_LEFT) }}</dd></div>
            </dl>
        </aside>

        <section class="event-form-panel" aria-labelledby="group-form-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">01</span><h2 id="group-form-title">Group details</h2></div>
                <p><span aria-hidden="true">*</span> Required fields</p>
            </div>

            <form wire:submit="save" class="event-form">
                <div class="event-field event-field-wide">
                    <label for="name">Group name <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Choose a name that members can easily recognize.</p>
                    <input type="text" id="name" wire:model="name" placeholder="e.g. North District Families" autocomplete="off" class="@error('name') is-invalid @enderror">
                    @error('name')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-field event-field-wide">
                    <label for="photo">Group picture</label>
                    <p class="event-field-hint">Upload a new JPG, PNG, or WEBP image up to 5 MB.</p>
                    <input type="file" id="photo" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="@error('photo') is-invalid @enderror">
                    @error('photo')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                    <div wire:loading wire:target="photo" class="event-field-hint">Uploading preview…</div>
                    @if($photo)<img src="{{ $photo->temporaryUrl() }}" alt="New group picture preview" class="group-photo-preview">@elseif($smallGroup->photo_path)<img src="{{ route('small-group-photo', ['filename' => basename($smallGroup->photo_path)]) }}" alt="{{ $smallGroup->name }}" class="group-photo-preview">@endif
                </div>

                <div class="event-field event-field-wide">
                    <label for="description">Description</label>
                    <p class="event-field-hint">Describe who the group serves or when it gathers.</p>
                    <textarea id="description" wire:model="description" rows="4" placeholder="Add a short description…" class="@error('description') is-invalid @enderror"></textarea>
                    @error('description')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                @include('livewire.partials.tag-picker', ['tagLabel' => 'Group tags', 'tagHelp' => 'Select reusable group tags or create a new one.', 'tagInputId' => 'group-tag-search'])

                <fieldset class="event-field event-field-wide group-leader-picker @error('leader_ids') is-invalid @enderror @error('leader_ids.*') is-invalid @enderror">
                    <legend>Group leaders <span aria-hidden="true">*</span></legend>
                    <p class="event-field-hint">Select one or more active users from any role.</p>
                    <div class="group-leader-toolbar">
                        <div class="group-leader-search">
                            <x-heroicon-o-magnifying-glass aria-hidden="true" />
                            <label class="sr-only" for="leader-search">Search group leaders</label>
                            <input id="leader-search" type="search" wire:model.live.debounce.250ms="leaderSearch" placeholder="Search by name, email, or role" autocomplete="off">
                            @if($leaderSearch !== '')<button type="button" wire:click="$set('leaderSearch', '')" aria-label="Clear leader search">Clear</button>@endif
                        </div>
                        <div class="group-leader-picker-meta"><span>{{ count($leader_ids) }} {{ Str::plural('leader', count($leader_ids)) }} selected</span>@if($leaderSearch !== '')<span>{{ $users->count() }} matching {{ Str::plural('user', $users->count()) }}</span>@endif</div>
                    </div>
                    <div class="group-leader-options">
                        @forelse($users as $user)
                            <label><input type="checkbox" value="{{ $user->id }}" wire:model="leader_ids"><span>{{ $user->name }} <small>{{ $user->roles->pluck('name')->join(', ') }}</small></span></label>
                        @empty
                            <p class="event-field-hint">{{ $leaderSearch !== '' ? 'No active users match your search.' : 'No active users are available.' }}</p>
                        @endforelse
                    </div>
                    @error('leader_ids')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                    @error('leader_ids.*')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <div class="event-field">
                    <label for="status">Status <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Control whether this group is currently operating.</p>
                    <select id="status" wire:model="status" class="@error('status') is-invalid @enderror">
                        @foreach($statuses as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach
                    </select>
                    @error('status')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-form-actions">
                    <a href="{{ route('small-groups.show', $smallGroup) }}" class="event-button-secondary" wire:navigate>Cancel</a>
                    <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save changes</span><span wire:loading wire:target="save">Saving…</span>
                        <x-heroicon-o-chevron-right wire:loading.remove wire:target="save" aria-hidden="true" />
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
