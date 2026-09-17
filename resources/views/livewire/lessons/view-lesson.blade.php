<div class="event-page lesson-detail-page lesson-reader">
    <x-slot:headerTitle>Lesson Details</x-slot:headerTitle>
    <x-slot:headerSubtitle>Shared church curriculum</x-slot:headerSubtitle>

    <x-page-header :title="$lesson->title" subtitle="The same teaching material is available to every cell group." :backRoute="route('lessons.index')" backLabel="All lessons">
        <x-slot:actions>@can('lessons.update')<a href="{{ route('lessons.index') }}?edit={{ $lesson->id }}" class="event-button-secondary" wire:navigate><x-heroicon-o-pencil-square />Edit lesson</a>@endcan</x-slot:actions>
    </x-page-header>

    <section class="lesson-reader-hero" aria-label="Lesson overview">
        <span class="lesson-reader-number"><small>Lesson</small><strong>{{ str_pad($lesson->order, 2, '0', STR_PAD_LEFT) }}</strong></span>
        <div class="lesson-reader-copy">
            <div><span @class(['lesson-state', 'is-published' => $lesson->status === 'published'])><i></i>{{ ucfirst($lesson->status) }}</span><span class="lesson-shared-label"><x-heroicon-o-user-group />All cell groups</span></div>
            <h2>{{ $lesson->title }}</h2>
            <p>{{ $lesson->description ?: 'No leader summary has been added to this lesson.' }}</p>
        </div>
        @can('lesson_progress.view')
            @if($group)<div class="lesson-progress-ring" style="--completion: {{ $totalMembers ? round(($completedCount / $totalMembers) * 100) : 0 }}%"><div><strong>{{ $totalMembers ? round(($completedCount / $totalMembers) * 100) : 0 }}%</strong><small>{{ $group->name }}</small></div></div>@endif
        @endcan
    </section>

    <div @class(['lesson-reader-layout', 'is-content-only' => Gate::denies('lesson_progress.view')])>
        <article class="lesson-reading-panel" aria-labelledby="lesson-content-title">
            <header><span class="lesson-kicker">Teaching material</span><h2 id="lesson-content-title">Leader’s guide</h2></header>
            <div class="lesson-reading-body">
                @if($lesson->content)<x-editorjs-renderer :content="$lesson->content" />@else<div class="lesson-empty-content"><x-heroicon-o-document-text /><h3>No teaching material yet</h3><p>Edit this lesson to add scripture, discussion points, and leader notes.</p></div>@endif
            </div>
        </article>

        @can('lesson_progress.view')
            <aside class="lesson-progress-panel" aria-labelledby="progress-title">
                <header><span class="lesson-kicker">Group progress</span><h2 id="progress-title">Follow the room</h2><p>Select a cell group to review its members.</p></header>
                <label for="group-selector">Cell group</label>
                <div class="lesson-group-select"><x-heroicon-o-user-group /><select id="group-selector" wire:model.live="groupId"><option value="">Select a group</option>@foreach($groups as $availableGroup)<option value="{{ $availableGroup->id }}">{{ $availableGroup->name }}</option>@endforeach</select><x-heroicon-o-chevron-down /></div>
                @if($group)
                    <dl class="lesson-progress-metrics"><div class="is-complete"><dt>Completed</dt><dd>{{ $completedCount }}</dd></div><div class="is-progress"><dt>In progress</dt><dd>{{ $inProgressCount }}</dd></div><div><dt>Not started</dt><dd>{{ $notStartedCount }}</dd></div></dl>
                @else
                    <div class="lesson-progress-prompt"><x-heroicon-o-cursor-arrow-rays /><p>Choose a cell group to see its lesson progress.</p></div>
                @endif
            </aside>
        @endcan
    </div>

    @can('lesson_progress.view')
        @if($group)
            <section class="lesson-roster" aria-labelledby="roster-title">
                <header class="lesson-roster-heading"><div><span class="lesson-kicker">{{ $group->name }}</span><h2 id="roster-title">Member progress</h2><p>Update each person as the group works through this lesson.</p></div><span>{{ $totalMembers }} {{ Str::plural('member', $totalMembers) }}</span></header>
                @if($group->members->isNotEmpty())
                    <ul class="lesson-roster-list">
                        @foreach($group->members as $member)
                            @php $currentStatus = $progress->where('small_group_member_id', $member->id)->first()?->status ?? 'not_started'; @endphp
                            <li><span class="event-member-initials">{{ collect(explode(' ', $member->user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span><div class="lesson-member-copy"><strong>{{ $member->user->name }}</strong><small>{{ $member->user->email }}</small></div>@can('lesson_progress.update')<div class="lesson-status-control" aria-label="Progress for {{ $member->user->name }}"><button wire:click="updateMemberProgress({{ $member->id }}, 'not_started')" @class(['is-active' => $currentStatus === 'not_started'])><i></i>Not started</button><button wire:click="updateMemberProgress({{ $member->id }}, 'in_progress')" @class(['is-active is-progress' => $currentStatus === 'in_progress'])><i></i>In progress</button><button wire:click="updateMemberProgress({{ $member->id }}, 'completed')" @class(['is-active is-complete' => $currentStatus === 'completed'])><i></i>Completed</button></div>@else<span @class(['lesson-state', 'is-published' => $currentStatus === 'completed'])>{{ ucwords(str_replace('_', ' ', $currentStatus)) }}</span>@endcan</li>
                        @endforeach
                    </ul>
                @else
                    <div class="lesson-empty-content"><x-heroicon-o-user-plus /><h3>No members to track</h3><p>Add members to this cell group before recording progress.</p><a href="{{ route('small-groups.members', $group) }}" class="event-button-secondary" wire:navigate>Manage members</a></div>
                @endif
            </section>
        @endif
    @endcan
</div>
