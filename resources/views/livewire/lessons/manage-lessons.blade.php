<div class="event-page lesson-workspace">
    <x-slot:headerTitle>Lessons</x-slot:headerTitle>
    <x-slot:headerSubtitle>One teaching plan, shared across every cell group</x-slot:headerSubtitle>

    <x-page-header title="Shared curriculum" subtitle="Prepare each lesson once. Every cell group receives the same published teaching plan." :backRoute="route('home')" backLabel="Overview">
        <x-slot:actions>
            @can('lessons.create')
                @if(!$showForm)<button wire:click="showCreateForm" class="event-button-primary lesson-new-button"><x-heroicon-o-plus />Create lesson</button>@endif
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if(session('success'))<div class="event-alert event-alert-success" role="status"><x-heroicon-o-check /><span>{{ session('success') }}</span></div>@endif

    <section class="lesson-overview" aria-label="Curriculum overview">
        <div class="lesson-overview-copy">
            <span class="lesson-kicker"><x-heroicon-o-sparkles />Church-wide learning path</span>
            <h2>Keep every group moving through the same message.</h2>
            <p>Draft privately, publish when the material is ready, and follow each cell group’s progress from one place.</p>
        </div>
        <dl class="lesson-overview-stats">
            <div><dt>Lessons</dt><dd>{{ $totalLessons }}</dd></div>
            <div><dt>Published</dt><dd>{{ $publishedLessons }}</dd></div>
            <div><dt>Cell groups</dt><dd>{{ $cellGroupCount }}</dd></div>
        </dl>
    </section>

    @if($showForm)
        <section class="lesson-composer" aria-labelledby="lesson-form-title">
            <aside class="lesson-composer-intro">
                <span class="lesson-composer-step">{{ $editingLessonId ? 'Editing lesson' : 'New lesson' }}</span>
                <h2 id="lesson-form-title">{{ $editingLessonId ? 'Refine the teaching plan.' : 'Build the next teaching plan.' }}</h2>
                <p>The title and summary help leaders prepare. The lesson body is the shared material they will teach.</p>
                <ul><li><x-heroicon-o-check />One source for every group</li><li><x-heroicon-o-check />Draft before publishing</li><li><x-heroicon-o-check />Progress stays group-specific</li></ul>
            </aside>

            <div class="event-form-panel lesson-composer-panel">
                <div class="event-section-heading"><div><span class="event-section-index">01</span><h2>Lesson details</h2></div><button type="button" wire:click="cancelForm" class="group-close-button" aria-label="Close lesson form"><x-heroicon-o-x-mark /></button></div>
                <form wire:submit="save" class="event-form lesson-form">
                    <div class="event-field event-field-wide">
                        <label for="title">Lesson title <span aria-hidden="true">*</span></label><p class="event-field-hint">Use a short title leaders can recognize at a glance.</p>
                        <input id="title" type="text" wire:model="title" class="@error('title') is-invalid @enderror" placeholder="e.g. Living with purpose" autofocus>
                        @error('title')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="event-field"><label for="order">Sequence</label><p class="event-field-hint">Where this appears in the teaching plan.</p><input id="order" type="number" min="1" wire:model="order">@error('order')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    @can('lessons.publish')<div class="event-field"><label for="status">Visibility</label><p class="event-field-hint">Drafts stay hidden from view-only roles.</p><select id="status" wire:model="status">@foreach($statuses as $lessonStatus)<option value="{{ $lessonStatus }}">{{ ucfirst($lessonStatus) }}</option>@endforeach</select></div>@else<input type="hidden" wire:model="status">@endcan
                    <div class="event-field event-field-wide"><label for="description">Leader summary</label><p class="event-field-hint">Explain the lesson’s focus in one or two sentences.</p><textarea id="description" rows="3" wire:model="description" placeholder="What should leaders understand before teaching this lesson?"></textarea>@error('description')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror</div>
                    <div class="event-field event-field-wide lesson-editor-field" wire:ignore
                         x-data="{ editor: null, content: @entangle('content'), init() { let data = {}; try { data = this.content ? JSON.parse(this.content) : {}; } catch (e) { data = {}; } this.editor = new window.EditorJS({ holder: 'shared-lesson-editor-{{ $editingLessonId ?? 'new' }}', tools: window.EditorJSTools, data, placeholder: 'Start with the main scripture, discussion points, or teaching notes…', onChange: async () => { this.content = JSON.stringify(await this.editor.save()); } }); } }">
                        <div class="lesson-editor-label"><div><label>Teaching material</label><p class="event-field-hint">Use headings, lists, quotes, and highlighted notes to make the lesson easy to lead.</p></div><span>Shared with all groups</span></div>
                        <div id="shared-lesson-editor-{{ $editingLessonId ?? 'new' }}" class="group-editor lesson-editor"></div>
                    </div>
                    <div class="event-form-actions lesson-form-actions"><button type="button" wire:click="cancelForm" class="event-button-secondary">Cancel</button><button type="submit" class="event-button-primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $editingLessonId ? 'Save lesson' : 'Create lesson' }}</span><span wire:loading wire:target="save">Saving…</span><x-heroicon-o-arrow-right wire:loading.remove wire:target="save" /></button></div>
                </form>
            </div>
        </section>
    @endif

    <section class="lesson-library" aria-labelledby="lessons-title">
        <header class="lesson-library-heading"><div><span class="lesson-kicker">Teaching sequence</span><h2 id="lessons-title">Curriculum library</h2><p>Open a lesson to read the material or review progress by cell group.</p></div><span class="lesson-count">{{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}</span></header>
        @if($lessons->isNotEmpty())
            <ol class="lesson-library-list">
                @foreach($lessons as $lesson)
                    <li class="lesson-library-item">
                        <a href="{{ route('lessons.show', $lesson) }}" class="lesson-library-main" wire:navigate><span class="lesson-sequence"><small>Lesson</small>{{ str_pad($lesson->order, 2, '0', STR_PAD_LEFT) }}</span><span class="lesson-library-copy"><strong>{{ $lesson->title }}</strong><small>{{ $lesson->description ?: 'No leader summary has been added yet.' }}</small></span></a>
                        <div class="lesson-library-meta"><span @class(['lesson-state', 'is-published' => $lesson->status === 'published'])><i></i>{{ ucfirst($lesson->status) }}</span><span class="lesson-completions">{{ $lesson->completed_count }} completed</span></div>
                        <div class="event-row-actions lesson-row-actions"><a href="{{ route('lessons.show', $lesson) }}" title="Open {{ $lesson->title }}" wire:navigate><x-heroicon-o-arrow-up-right /></a>@can('lessons.update')<button wire:click="editLesson({{ $lesson->id }})" title="Edit {{ $lesson->title }}"><x-heroicon-o-pencil-square /></button>@endcan @can('lessons.delete')<button wire:click="deleteLesson({{ $lesson->id }})" wire:confirm="Delete {{ $lesson->title }}? This will also remove its progress records." title="Delete {{ $lesson->title }}"><x-heroicon-o-trash /></button>@endcan</div>
                    </li>
                @endforeach
            </ol>
        @else
            <div class="lesson-empty-state"><span><x-heroicon-o-book-open /></span><div><h3>Your shared curriculum starts here</h3><p>Create the first lesson, keep it as a draft while preparing, then publish it to every cell group.</p></div>@can('lessons.create')<button wire:click="showCreateForm" class="event-button-primary">Create first lesson</button>@endcan</div>
        @endif
    </section>
</div>
