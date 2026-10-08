<x-settings-shell active="attendance" title="Attendance defaults" description="Choose the starting attendance rules applied when an administrator creates a new event.">
    <header class="settings-section-heading"><div><span class="event-eyebrow">New events only</span><h2>Default attendance policy</h2></div><p>Existing events are never changed.</p></header>
    <form wire:submit="save" class="event-form settings-form attendance-settings-form">
        <div class="attendance-default-grid event-field-wide">
            <section class="attendance-default-panel">
                <header><span class="event-eyebrow">Policy</span><h3>Attendance requirement</h3><p>Choose whether new events should begin with attendance tracking enabled.</p></header>
                <label class="settings-toggle attendance-main-toggle">
                    <input type="checkbox" wire:model.live="attendance_required">
                    <span><strong>Require attendance by default</strong><small>Administrators can still change this while creating an individual event.</small></span>
                    <em @class(['is-active' => $attendance_required])>{{ $attendance_required ? 'On' : 'Off' }}</em>
                </label>
            </section>

            <section class="attendance-default-panel">
                <header><span class="event-eyebrow">Engagement</span><h3>Point values</h3><p>Set the score change recorded when a required member is present or absent.</p></header>
                <div class="attendance-points-grid">
                    <div class="event-field"><label for="reward-points">Present reward</label><div class="attendance-number-input"><input id="reward-points" type="number" min="0" max="100" wire:model="reward_points"><span>points</span></div>@error('reward_points')<p class="event-field-error">{{ $message }}</p>@enderror</div>
                    <div class="event-field"><label for="penalty-points">Absence penalty</label><div class="attendance-number-input"><input id="penalty-points" type="number" min="0" max="100" wire:model="penalty_points"><span>points</span></div>@error('penalty_points')<p class="event-field-error">{{ $message }}</p>@enderror</div>
                </div>
            </section>
        </div>

        @if($attendance_required)
            <section class="attendance-audience-panel event-field-wide">
                <header><div><span class="event-eyebrow">Audience</span><h3>Who is expected to attend?</h3></div><p>This becomes the starting audience for every new required-attendance event.</p></header>
                <fieldset class="settings-audience attendance-mode-options"><legend class="sr-only">Default required audience</legend>
                    <label @class(['is-selected' => $audience_mode === 'all_active'])><input type="radio" value="all_active" wire:model.live="audience_mode"><span><strong>All active members</strong><small>Every active member is expected to attend.</small></span></label>
                    <label @class(['is-selected' => $audience_mode === 'selected'])><input type="radio" value="selected" wire:model.live="audience_mode"><span><strong>Selected roles and groups</strong><small>Use a smaller, reusable audience.</small></span></label>
                </fieldset>
                @if($audience_mode === 'selected')
                    <div class="attendance-audience-selection">
                        <fieldset class="event-field"><legend>Roles</legend><p class="event-field-hint">Include members with any selected role.</p><div class="settings-check-grid">@foreach($roles as $role)<label><input type="checkbox" value="{{ $role->slug }}" wire:model="role_audience"><span>{{ $role->name }}</span></label>@endforeach</div></fieldset>
                        <fieldset class="event-field"><legend>Small groups</legend><p class="event-field-hint">Include members from any selected group.</p><div class="settings-check-grid">@forelse($smallGroups as $group)<label><input type="checkbox" value="{{ $group->id }}" wire:model="small_group_audience"><span>{{ $group->name }}</span></label>@empty<p class="event-field-hint">No active groups available.</p>@endforelse</div></fieldset>
                    </div>
                @endif
                @error('audience')<p class="event-field-error">{{ $message }}</p>@enderror
            </section>
        @else
            <section class="attendance-audience-panel attendance-audience-disabled event-field-wide">
                <span class="event-eyebrow">Audience</span><h3>No required audience</h3><p>Turn on attendance requirements to choose which members new events should expect.</p>
            </section>
        @endif
        <div class="event-form-actions"><button class="event-button-primary">Save defaults</button></div>
    </form>
</x-settings-shell>
