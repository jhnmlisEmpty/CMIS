<div class="event-page group-page group-detail-page">
    <x-slot:headerTitle>Small Group Details</x-slot:headerTitle>
    <x-slot:headerSubtitle>True Vine World Harvest Church - Pangasinan</x-slot:headerSubtitle>

    <x-page-header
        :title="$smallGroup->name"
        subtitle="Manage this community’s people, leadership, and learning plan."
        :backRoute="route('small-groups.index')"
        backLabel="Small groups">
        <x-slot:actions>
            @can('small_groups.update')<a href="{{ route('small-groups.edit', $smallGroup) }}" class="event-button-secondary" wire:navigate>
                <x-heroicon-o-pencil-square aria-hidden="true" />
                Edit group
            </a>@endcan
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status"><x-heroicon-o-check aria-hidden="true" /><span>{{ session('success') }}</span></div>
    @endif

    <section class="event-detail-hero group-detail-hero" aria-label="Group overview">
        @if($smallGroup->photo_path)<img src="{{ route('small-group-photo', ['filename' => basename($smallGroup->photo_path)]) }}" alt="{{ $smallGroup->name }}" class="group-hero-mark group-photo">@else<span class="group-hero-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($smallGroup->name, 0, 2)) }}</span>@endif
        <div class="event-detail-copy">
            <span @class(['group-status', 'is-active' => $smallGroup->status === 'active'])><i></i>{{ ucfirst($smallGroup->status) }}</span>
            <p>{{ $smallGroup->leader?->name ?? 'No leader assigned' }}</p>
            <div>
                <x-heroicon-o-user-group aria-hidden="true" />
                <span>Group leader</span>
            </div>
        </div>
        <div class="group-hero-metrics">
            <div><strong>{{ $smallGroup->members->count() }}</strong><span>{{ Str::plural('member', $smallGroup->members->count()) }}</span></div>
            <div><strong>{{ $smallGroup->lessons->count() }}</strong><span>{{ Str::plural('lesson', $smallGroup->lessons->count()) }}</span></div>
        </div>
    </section>

    <section class="group-analytics-strip" aria-label="Cell-group analytics">
        <div><small>Required attendance</small><strong>{{ $groupAnalytics['attendance_rate'] === null ? 'No data' : $groupAnalytics['attendance_rate'].'%' }}</strong><span>{{ $groupAnalytics['present'] }} present, {{ $groupAnalytics['absent'] }} absent</span></div>
        <div><small>Lesson completion</small><strong>{{ $groupAnalytics['lesson_rate'] === null ? 'No data' : $groupAnalytics['lesson_rate'].'%' }}</strong><span>{{ $groupAnalytics['completed_lessons'] }}/{{ $groupAnalytics['lesson_total'] }} member lessons</span></div>
        @can('analytics.view')<a href="{{ route('analytics.index', ['tab' => 'groups']) }}" wire:navigate>Open group analytics <x-heroicon-o-chevron-right /></a>@endcan
    </section>

    <div class="group-overview-grid">
        <section class="event-checkin-panel" aria-labelledby="group-about-title">
            <div class="event-section-heading"><div><span class="event-section-index">01</span><h2 id="group-about-title">About this group</h2></div></div>
            <div class="group-about-content">
                <p>{{ $smallGroup->description ?: 'No description has been added for this small group yet.' }}</p>
                <dl>
                    <div><dt>Leader</dt><dd>{{ $smallGroup->leader?->name ?? 'Not assigned' }}</dd></div>
                    <div><dt>Created</dt><dd>{{ $smallGroup->created_at->format('M j, Y') }}</dd></div>
                    <div><dt>Group ID</dt><dd>#{{ str_pad($smallGroup->id, 4, '0', STR_PAD_LEFT) }}</dd></div>
                </dl>
            </div>
        </section>

        <aside class="group-actions-panel" aria-labelledby="group-actions-title">
            <div class="event-section-heading"><div><span class="event-section-index">02</span><h2 id="group-actions-title">Group workspace</h2></div></div>
            <div class="group-workspace-links">
                @can('group_members.view')<a href="{{ route('small-groups.members', $smallGroup) }}" wire:navigate>
                    <span><x-heroicon-o-user-group aria-hidden="true" /></span>
                    <div><strong>Manage members</strong><small>Add, remove, or update status</small></div>
                    <x-heroicon-o-chevron-right aria-hidden="true" />
                </a>@endcan
                @can('lessons.view')<a href="{{ route('lessons.index') }}" wire:navigate>
                    <span><x-heroicon-o-book-open aria-hidden="true" /></span>
                    <div><strong>Manage lessons</strong><small>Build and organize the curriculum</small></div>
                    <x-heroicon-o-chevron-right aria-hidden="true" />
                </a>@endcan
            </div>
        </aside>
    </div>

    <div class="group-content-grid">
        @can('group_members.view')<section class="event-attendance-panel" aria-labelledby="group-members-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">03</span><h2 id="group-members-title">Members</h2></div>
                <a href="{{ route('small-groups.members', $smallGroup) }}" class="group-section-link" wire:navigate>Manage <span aria-hidden="true">→</span></a>
            </div>
            @if($smallGroup->members->count() > 0)
                <ul class="group-people-list">
                    @foreach($smallGroup->members as $member)
                        <li>
                            <span class="event-member-initials">{{ collect(explode(' ', $member->user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                            <div><strong>{{ $member->user->name }}</strong><small>{{ $member->user->email }}</small></div>
                            <span @class(['group-status', 'is-active' => $member->status === 'active'])><i></i>{{ ucfirst($member->status) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="event-empty-state event-empty-compact"><span class="event-empty-icon"><x-heroicon-o-user-plus /></span><h3>No members yet</h3><p>Add the first member to begin building this community.</p><a href="{{ route('small-groups.members', $smallGroup) }}" class="event-button-secondary" wire:navigate>Add members</a></div>
            @endif
        </section>@endcan

        @can('lessons.view')<section class="event-attendance-panel" aria-labelledby="group-lessons-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">04</span><h2 id="group-lessons-title">Lessons</h2></div>
                <a href="{{ route('lessons.index') }}" class="group-section-link" wire:navigate>Manage <span aria-hidden="true">→</span></a>
            </div>
            @if($smallGroup->lessons->count() > 0)
                <ol class="group-lesson-list">
                    @foreach($smallGroup->lessons->sortBy('order') as $lesson)
                        @php $totalMembers = $smallGroup->members->count(); $completedCount = $lesson->progress->where('status', 'completed')->count(); @endphp
                        <li>
                            <span class="group-lesson-number">{{ str_pad($lesson->order, 2, '0', STR_PAD_LEFT) }}</span>
                            <div><a href="{{ route('lessons.show', $lesson) }}?group={{ $smallGroup->id }}" wire:navigate>{{ $lesson->title }}</a><small>{{ $lesson->description ?: 'No description' }}</small></div>
                            <span class="group-lesson-progress">{{ $completedCount }}/{{ $totalMembers }} complete</span>
                            <div class="event-row-actions">
                                <a href="{{ route('lessons.show', $lesson) }}?group={{ $smallGroup->id }}" title="Open lesson" wire:navigate><x-heroicon-o-chevron-right /></a>
                                @can('lessons.update')<a href="{{ route('lessons.index') }}?edit={{ $lesson->id }}" title="Edit lesson" wire:navigate><x-heroicon-o-pencil-square /></a>@endcan
                                @can('lessons.delete')<button wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete {{ $lesson->title }}?" title="Delete lesson"><x-heroicon-o-trash /></button>@endcan
                            </div>
                        </li>
                    @endforeach
                </ol>
            @else
                <div class="event-empty-state event-empty-compact"><span class="event-empty-icon"><x-heroicon-o-book-open /></span><h3>No lessons yet</h3><p>Build the shared curriculum for every cell group.</p><a href="{{ route('lessons.index') }}" class="event-button-secondary" wire:navigate>Open lessons</a></div>
            @endif
        </section>@endcan
    </div>
</div>
