<div class="event-page group-page group-lessons-page">
    <x-slot:headerTitle>Manage Lessons</x-slot:headerTitle>

    <x-page-header
        :title="$smallGroup->name . ' lessons'"
        subtitle="Create the curriculum, organize lesson order, and track learning progress."
        :backRoute="route('small-groups.show', $smallGroup)"
        backLabel="Group details">
        <x-slot:actions>
            @if(!$showForm)
                <button wire:click="showCreateForm" class="event-button-primary">
                    <x-heroicon-o-plus aria-hidden="true" />New lesson
                </button>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))
        <div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>
    @endif

    <section class="group-workspace-summary" aria-label="Lesson summary">
        <div><span class="group-mark"><x-heroicon-o-book-open /></span><div><span class="event-eyebrow">Curriculum workspace</span><strong>{{ $smallGroup->lessons->count() }} {{ Str::plural('lesson', $smallGroup->lessons->count()) }} prepared</strong></div></div>
        <p>{{ $smallGroup->members->count() }} {{ Str::plural('member', $smallGroup->members->count()) }} available for progress tracking.</p>
    </section>

    @if($showForm)
        <section class="event-form-panel group-lesson-form-panel" aria-labelledby="lesson-form-title">
            <div class="event-section-heading">
                <div><span class="event-section-index">01</span><h2 id="lesson-form-title">{{ $editingLessonId ? 'Edit lesson' : 'Create a lesson' }}</h2></div>
                <button wire:click="cancelForm" class="group-close-button" aria-label="Close lesson form"><x-heroicon-o-x-mark /></button>
            </div>
            <form wire:submit="save" class="event-form">
                <div class="event-field event-field-wide">
                    <label for="title">Lesson title <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Use a concise title that communicates the lesson focus.</p>
                    <input type="text" id="title" wire:model="title" placeholder="e.g. Living with purpose" class="@error('title') is-invalid @enderror">
                    @error('title')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="event-field">
                    <label for="order">Lesson order <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Position in the curriculum.</p>
                    <input type="number" id="order" wire:model="order" min="1" class="@error('order') is-invalid @enderror">
                    @error('order')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div class="event-field">
                    <label for="status">Status <span aria-hidden="true">*</span></label>
                    <p class="event-field-hint">Publish when ready for members.</p>
                    <select id="status" wire:model="status">@foreach($statuses as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select>
                </div>
                <div class="event-field event-field-wide">
                    <label for="description">Description</label>
                    <p class="event-field-hint">Add a short overview for leaders and members.</p>
                    <textarea id="description" wire:model="description" rows="2" placeholder="What will this lesson cover?"></textarea>
                </div>
                <div class="event-field event-field-wide" wire:ignore
                     x-data="{ editor: null, content: @entangle('content'), init() { this.initEditor(); }, initEditor() { let initialData = {}; try { if (this.content && this.content.trim()) initialData = JSON.parse(this.content); } catch (e) { initialData = {}; } this.editor = new window.EditorJS({ holder: 'editorjs-{{ $editingLessonId ?? 'new' }}', tools: window.EditorJSTools, data: initialData, placeholder: 'Start writing your lesson content…', onChange: async () => { const data = await this.editor.save(); this.content = JSON.stringify(data); } }); } }"
                     x-on:save-editor.window="if (editor) { editor.save().then(data => { content = JSON.stringify(data); }); }">
                    <label>Lesson content</label>
                    <p class="event-field-hint">Build the teaching outline with headings, lists, quotes, and notes.</p>
                    <div id="editorjs-{{ $editingLessonId ?? 'new' }}" class="group-editor"></div>
                </div>
                <div class="event-form-actions">
                    <button type="button" wire:click="cancelForm" class="event-button-secondary">Cancel</button>
                    <button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editingLessonId ? 'Save changes' : 'Create lesson' }}</span><span wire:loading wire:target="save">Saving…</span>
                        <x-heroicon-o-chevron-right wire:loading.remove wire:target="save" />
                    </button>
                </div>
            </form>
        </section>
    @endif

    @if($viewingLesson)
        <div class="group-modal" role="dialog" aria-modal="true" aria-labelledby="lesson-preview-title">
            <button class="group-modal-backdrop" wire:click="closeViewLesson" aria-label="Close lesson preview"></button>
            <article class="group-modal-panel">
                <header>
                    <div><span class="event-eyebrow">Lesson {{ str_pad($viewingLesson->order, 2, '0', STR_PAD_LEFT) }}</span><h2 id="lesson-preview-title">{{ $viewingLesson->title }}</h2></div>
                    <button wire:click="closeViewLesson" class="group-close-button" aria-label="Close lesson preview"><x-heroicon-o-x-mark /></button>
                </header>
                <div class="group-modal-content">
                    @if($viewingLesson->description)<p class="group-modal-description">{{ $viewingLesson->description }}</p>@endif
                    @if($viewingLesson->content)<x-editorjs-renderer :content="$viewingLesson->content" />@else<div class="event-empty-state event-empty-compact"><h3>No content yet</h3><p>Edit this lesson to add teaching material.</p></div>@endif
                </div>
                <footer>
                    <div class="event-section-heading"><div><span class="event-section-index">P</span><h2>Member progress</h2></div></div>
                    @if($smallGroup->members->count() > 0)
                        <ul class="group-modal-progress">
                            @foreach($smallGroup->members as $member)
                                @php $progress = $viewingLesson->progress->where('small_group_member_id', $member->id)->first(); $currentStatus = $progress?->status ?? 'not_started'; @endphp
                                <li><span>{{ $member->user->name }}</span><select wire:change="updateMemberProgress({{ $member->id }}, {{ $viewingLesson->id }}, $event.target.value)">@foreach($progressStatuses as $ps)<option value="{{ $ps }}" {{ $currentStatus === $ps ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $ps)) }}</option>@endforeach</select></li>
                            @endforeach
                        </ul>
                    @else<p class="group-modal-empty">No members in this group yet.</p>@endif
                </footer>
            </article>
        </div>
    @endif

    <section class="event-attendance-panel group-lessons-directory" aria-labelledby="all-lessons-title">
        <div class="event-section-heading">
            <div><span class="event-section-index">{{ $showForm ? '02' : '01' }}</span><h2 id="all-lessons-title">Curriculum</h2></div>
            <p>{{ $smallGroup->lessons->count() }} {{ Str::plural('lesson', $smallGroup->lessons->count()) }}</p>
        </div>
        @if($smallGroup->lessons->count() > 0)
            <ol class="group-curriculum-list">
                @foreach($smallGroup->lessons->sortBy('order') as $lesson)
                    <li>
                        <span class="group-lesson-number">{{ str_pad($lesson->order, 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="group-curriculum-copy"><a href="{{ route('small-groups.lessons.show', [$smallGroup, $lesson]) }}" wire:navigate>{{ $lesson->title }}</a><small>{{ $lesson->description ?: 'No description added' }}</small></div>
                        <span @class(['group-status', 'is-active' => $lesson->status === 'published'])><i></i>{{ ucfirst($lesson->status) }}</span>
                        <div class="event-row-actions">
                            <button wire:click="viewLesson({{ $lesson->id }})" title="Preview lesson" aria-label="Preview {{ $lesson->title }}"><x-heroicon-o-eye /></button>
                            <button wire:click="editLesson({{ $lesson->id }})" title="Edit lesson" aria-label="Edit {{ $lesson->title }}"><x-heroicon-o-pencil-square /></button>
                            <button wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete {{ $lesson->title }}?" title="Delete lesson" aria-label="Delete {{ $lesson->title }}"><x-heroicon-o-trash /></button>
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="event-empty-state"><span class="event-empty-icon"><x-heroicon-o-book-open /></span><h3>No lessons yet</h3><p>Create the first lesson to begin building this group’s curriculum.</p>@if(!$showForm)<button wire:click="showCreateForm" class="event-button-primary">Create a lesson</button>@endif</div>
        @endif
    </section>
</div>
