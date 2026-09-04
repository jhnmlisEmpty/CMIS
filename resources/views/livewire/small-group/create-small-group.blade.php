<div class="event-page group-page">
    <x-slot:headerTitle>Create Small Group</x-slot:headerTitle>

    <x-page-header
        title="Create a small group"
        subtitle="Set up a community, choose its leader, and make it ready for members and lessons."
        :backRoute="route('small-groups.index')"
        backLabel="Small groups" />

    <div class="event-editor-layout">
        <aside class="event-editor-intro" aria-label="Small group setup guide">
            <span class="event-eyebrow">Group setup</span>
            <h2>Make room for people to grow.</h2>
            <p>Give the group a clear identity and assign an active leader. Members and lesson plans can be added after creation.</p>
            <div class="event-editor-note">
                <x-heroicon-o-user-group aria-hidden="true" />
                <p>Each member can belong to one small group at a time.</p>
            </div>
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
                    <p class="event-field-hint">Optional JPG, PNG, or WEBP image up to 5 MB.</p>
                    <input type="file" id="photo" wire:model="photo" accept="image/jpeg,image/png,image/webp" class="@error('photo') is-invalid @enderror">
                    @error('photo')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                    <div wire:loading wire:target="photo" class="event-field-hint">Uploading preview…</div>
                    @if($photo)<img src="{{ $photo->temporaryUrl() }}" alt="Group picture preview" class="group-photo-preview">@endif
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
                    <a href="{{ route('small-groups.index') }}" class="event-button-secondary" wire:navigate>Cancel</a>
                    <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Create group</span><span wire:loading wire:target="save">Creating…</span>
                        <x-heroicon-o-chevron-right wire:loading.remove wire:target="save" aria-hidden="true" />
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
