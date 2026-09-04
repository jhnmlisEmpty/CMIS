<div class="event-page group-page">
    <x-slot:headerTitle>Edit Small Group</x-slot:headerTitle>

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
                <div><dt>Created</dt><dd>{{ $smallGroup->created_at?->format('M j, Y') ?? '—' }}</dd></div>
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

                <div class="event-field">
                    <label for="leader_id">Group leader <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Select an active member to lead.</p>
                    <select id="leader_id" wire:model="leader_id" class="@error('leader_id') is-invalid @enderror">
                        <option value="">Select a leader</option>
                        @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                    </select>
                    @error('leader_id')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

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
