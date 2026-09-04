<div class="event-page">
    <x-slot:headerTitle>Edit Event</x-slot:headerTitle>

    <x-page-header
        title="Edit event"
        subtitle="Keep the event information accurate for your team and attendees."
        :backRoute="route('events.view', $event->id)"
        backLabel="Event details" />

    <div class="event-editor-layout">
        <aside class="event-editor-intro" aria-label="Current event summary">
            <span class="event-eyebrow">Currently editing</span>
            <h2>{{ $event->title }}</h2>
            <p>Changes are reflected anywhere this event appears. Existing attendance records will stay connected.</p>

            <dl class="event-editor-summary">
                <div><dt>Created</dt><dd>{{ $event->created_at?->format('M j, Y') ?? '—' }}</dd></div>
                <div><dt>Event ID</dt><dd>#{{ str_pad($event->id, 4, '0', STR_PAD_LEFT) }}</dd></div>
            </dl>
        </aside>

        <section class="event-form-panel" aria-labelledby="event-form-title">
            <div class="event-section-heading">
                <div>
                    <span class="event-section-index">01</span>
                    <h2 id="event-form-title">Event details</h2>
                </div>
                <p><span aria-hidden="true">*</span> Required fields</p>
            </div>

            <form wire:submit.prevent="submit" class="event-form">
                <div class="event-field event-field-wide">
                    <label for="title">Event title <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Use a name members will recognize.</p>
                    <input type="text" id="title" wire:model="title" placeholder="e.g. Sunday worship service" autocomplete="off" class="@error('title') is-invalid @enderror">
                    @error('title')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-field event-field-wide">
                    <label for="description">Description</label>
                    <p class="event-field-hint">Share the purpose, schedule, or details attendees should know.</p>
                    <textarea id="description" wire:model="description" rows="4" placeholder="Add a short description for this gathering." class="@error('description') is-invalid @enderror"></textarea>
                    @error('description')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-field">
                    <label for="event_date">Date <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">When the gathering takes place.</p>
                    <input type="date" id="event_date" wire:model="event_date" class="@error('event_date') is-invalid @enderror">
                    @error('event_date')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-field">
                    <label for="event_type">Event type <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Group similar events together.</p>
                    <input type="text" id="event_type" wire:model="event_type" placeholder="e.g. Service" autocomplete="off" class="@error('event_type') is-invalid @enderror">
                    @error('event_type')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-field event-field-wide">
                    <label for="location">Location <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Add a room, building, or full address.</p>
                    <div class="event-input-icon">
                        <x-heroicon-o-map-pin aria-hidden="true" />
                        <input type="text" id="location" wire:model="location" placeholder="e.g. Main sanctuary" autocomplete="street-address" class="@error('location') is-invalid @enderror">
                    </div>
                    @error('location')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="event-form-actions">
                    <a href="{{ route('events.view', $event->id) }}" class="event-button-secondary" wire:navigate>Cancel</a>
                    <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading.remove wire:target="submit">Save changes</span>
                        <span wire:loading wire:target="submit">Saving…</span>
                        <x-heroicon-o-chevron-right wire:loading.remove wire:target="submit" aria-hidden="true" />
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
