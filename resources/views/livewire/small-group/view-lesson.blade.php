<div class="event-page group-page lesson-detail-page">
    <x-slot:headerTitle>Lesson Details</x-slot:headerTitle>

    <x-page-header
        :title="$lesson->title"
        :subtitle="$lesson->smallGroup->name . ' curriculum'"
        :backRoute="route('small-groups.lessons', $lesson->smallGroup)"
        backLabel="Lessons">
        <x-slot:actions>
            <a href="{{ route('small-groups.lessons', $lesson->smallGroup) }}?edit={{ $lesson->id }}" class="event-button-secondary" wire:navigate>
                <x-heroicon-o-pencil-square aria-hidden="true" />Edit lesson
            </a>
        </x-slot:actions>
    </x-page-header>

    <section class="event-detail-hero lesson-hero" aria-label="Lesson overview">
        <span class="lesson-hero-number"><small>Lesson</small><strong>{{ str_pad($lesson->order, 2, '0', STR_PAD_LEFT) }}</strong></span>
        <div class="event-detail-copy">
            <span @class(['group-status', 'is-active' => $lesson->status === 'published'])><i></i>{{ ucfirst($lesson->status) }}</span>
            <p>{{ $lesson->description ?: 'No lesson description has been added.' }}</p>
            <div><x-heroicon-o-book-open aria-hidden="true" /><span>{{ $lesson->smallGroup->name }}</span></div>
        </div>
        <div class="lesson-completion-ring" style="--completion: {{ $totalMembers > 0 ? round(($completedCount / $totalMembers) * 100) : 0 }}%">
            <div><strong>{{ $totalMembers > 0 ? round(($completedCount / $totalMembers) * 100) : 0 }}%</strong><small>complete</small></div>
        </div>
    </section>

    <div class="lesson-layout">
        <article class="event-attendance-panel lesson-content-panel" aria-labelledby="lesson-content-title">
            <div class="event-section-heading"><div><span class="event-section-index">01</span><h2 id="lesson-content-title">Lesson content</h2></div></div>
            <div class="lesson-content-body">
                @if($lesson->content)
                    <x-editorjs-renderer :content="$lesson->content" />
                @else
                    <div class="event-empty-state event-empty-compact"><span class="event-empty-icon"><x-heroicon-o-book-open /></span><h3>No content yet</h3><p>Edit this lesson to add teaching notes and materials.</p></div>
                @endif
            </div>
        </article>

        <aside class="lesson-progress-summary" aria-labelledby="lesson-summary-title">
            <div class="event-section-heading"><div><span class="event-section-index">02</span><h2 id="lesson-summary-title">Progress summary</h2></div></div>
            <dl>
                <div class="is-complete"><dt><i></i>Completed</dt><dd>{{ $completedCount }}</dd></div>
                <div class="is-progress"><dt><i></i>In progress</dt><dd>{{ $inProgressCount }}</dd></div>
                <div><dt><i></i>Not started</dt><dd>{{ $notStartedCount }}</dd></div>
                <div class="is-total"><dt>Total members</dt><dd>{{ $totalMembers }}</dd></div>
            </dl>
        </aside>
    </div>

    <section class="event-attendance-panel lesson-member-progress" aria-labelledby="member-progress-title">
        <div class="event-section-heading">
            <div><span class="event-section-index">03</span><h2 id="member-progress-title">Member progress</h2></div>
            <p>Select a status to update progress</p>
        </div>
        @if($lesson->smallGroup->members->count() > 0)
            <ul class="lesson-progress-list">
                @foreach($lesson->smallGroup->members as $member)
                    @php $currentStatus = $this->getMemberProgress($member->id); @endphp
                    <li>
                        <span class="event-member-initials">{{ collect(explode(' ', $member->user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                        <div class="lesson-member-copy"><strong>{{ $member->user->name }}</strong><small>{{ $member->user->email }}</small></div>
                        <div class="lesson-progress-controls" aria-label="Progress for {{ $member->user->name }}">
                            <button wire:click="updateMemberProgress({{ $member->id }}, 'not_started')" @class(['is-selected' => $currentStatus === 'not_started']) title="Not started"><i></i><span>Not started</span></button>
                            <button wire:click="updateMemberProgress({{ $member->id }}, 'in_progress')" @class(['is-selected is-progress' => $currentStatus === 'in_progress']) title="In progress"><i></i><span>In progress</span></button>
                            <button wire:click="updateMemberProgress({{ $member->id }}, 'completed')" @class(['is-selected is-complete' => $currentStatus === 'completed']) title="Completed"><i></i><span>Completed</span></button>
                        </div>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="event-empty-state event-empty-compact"><span class="event-empty-icon"><x-heroicon-o-user-plus /></span><h3>No members to track</h3><p>Add members to the group before recording lesson progress.</p><a href="{{ route('small-groups.members', $lesson->smallGroup) }}" class="event-button-secondary" wire:navigate>Add members</a></div>
        @endif
    </section>
</div>
