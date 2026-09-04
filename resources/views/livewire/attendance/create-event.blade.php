<div class="event-page">
    <x-slot:headerTitle>Create Event</x-slot:headerTitle>

    <x-page-header
        title="Create an event"
        subtitle="Set the essentials now. Attendance and check-in tools will be available from the event page."
        :backRoute="route('events.index')"
        backLabel="Events" />

    <div class="event-editor-layout">
        <aside class="event-editor-intro" aria-label="Event setup guide">
            <span class="event-eyebrow">Event setup</span>
            <h2>Bring everyone together.</h2>
            <p>Add a clear title, schedule, venue, and category so your team can find and manage this gathering.</p>

            <div class="event-editor-note">
                <x-heroicon-o-exclamation-triangle aria-hidden="true" />
                <p>You can start scanning member QR codes as soon as the event is created.</p>
            </div>
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
                    <a href="{{ route('events.index') }}" class="event-button-secondary" wire:navigate>Cancel</a>
                    <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="submit">
                        <span wire:loading.remove wire:target="submit">Create event</span>
                        <span wire:loading wire:target="submit">Creating…</span>
                        <x-heroicon-o-chevron-right wire:loading.remove wire:target="submit" aria-hidden="true" />
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
