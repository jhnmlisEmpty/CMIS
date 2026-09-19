@php $audienceLocked = isset($event) && $event->isAudienceLocked(); @endphp
<fieldset class="event-field event-field-wide analytics-requirements" @disabled($audienceLocked)>
    <legend>Attendance scoring</legend>
    @if($audienceLocked)
        <p class="event-field-hint">The required roster was fixed on {{ $event->audience_snapshotted_at->format('M j, Y g:i A') }}. Audience and point values are locked.</p>
    @else
        <p class="event-field-hint">Required events affect member scores after the event date passes.</p>
    @endif

    <label class="analytics-toggle">
        <input type="checkbox" wire:model.live="attendance_required">
        <span><strong>Require attendance</strong><small>Create an event-day roster and score each required member.</small></span>
    </label>

    @if($attendance_required)
        <div class="analytics-point-grid">
            <label>Points for attending<input type="number" min="0" max="100" wire:model="attendance_reward_points"></label>
            <label>Points deducted for absence<input type="number" min="0" max="100" wire:model="absence_penalty_points"></label>
        </div>
        @error('attendance_reward_points')<p class="event-field-error">{{ $message }}</p>@enderror
        @error('absence_penalty_points')<p class="event-field-error">{{ $message }}</p>@enderror

        <div class="analytics-audience-picker">
            <h3>Required audience</h3>
            <label class="analytics-choice"><input type="checkbox" wire:model.live="audience_all_active"><span>All active members</span></label>
            @unless($audience_all_active)
                <div class="analytics-audience-columns">
                    <div><strong>Roles</strong>@foreach($audienceRoles as $role)<label class="analytics-choice"><input type="checkbox" value="{{ $role }}" wire:model="selectedRoles"><span>{{ ucwords(str_replace('_', ' ', $role)) }}</span></label>@endforeach</div>
                    <div><strong>Cell groups</strong>@forelse($audienceGroups as $group)<label class="analytics-choice"><input type="checkbox" value="{{ $group->id }}" wire:model="selectedSmallGroups"><span>{{ $group->name }}</span></label>@empty<small>No active groups</small>@endforelse</div>
                    <div><strong>Specific members</strong><div class="analytics-member-choices">@forelse($audienceUsers as $member)<label class="analytics-choice"><input type="checkbox" value="{{ $member->id }}" wire:model="selectedUsers"><span>{{ $member->name }}</span></label>@empty<small>No active members</small>@endforelse</div></div>
                </div>
            @endunless
            @error('audience')<p class="event-field-error">{{ $message }}</p>@enderror
        </div>
    @endif
</fieldset>
