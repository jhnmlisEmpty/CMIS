<?php

namespace App\Livewire\SmallGroup;

use App\Livewire\Concerns\ManagesTags;
use App\Models\SmallGroup;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Create Small Group | True Vine World Harvest Church - Pangasinan')]
class CreateSmallGroup extends Component
{
    use ManagesTags;
    use WithFileUploads;

    public string $name = '';

    public string $description = '';

    public array $leader_ids = [];

    public string $leaderSearch = '';

    public string $status = 'active';

    public $photo;

    protected function tagType(): string
    {
        return Tag::TYPE_SMALL_GROUP;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'leader_ids' => ['required', 'array', 'min:1'],
            'leader_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('status', User::STATUS_ACTIVE))],
            'status' => ['required', 'in:'.implode(',', SmallGroup::STATUSES)],
            ...$this->tagRules(),
        ];
    }

    public function save(): void
    {
        Gate::authorize('small_groups.create');
        $validated = $this->validate();

        $leaderIds = $validated['leader_ids'];
        $tagIds = $validated['tag_ids'];
        $pendingTags = $validated['pending_tags'];
        unset($validated['leader_ids'], $validated['tag_ids'], $validated['pending_tags'], $validated['photo']);
        $validated['photo_path'] = $this->photo?->store('small-group-photos', 'local');

        DB::transaction(function () use ($validated, $leaderIds, $tagIds, $pendingTags): void {
            $smallGroup = SmallGroup::create($validated);
            $smallGroup->leaders()->attach($leaderIds);
            $this->syncTags($smallGroup, $tagIds, $pendingTags);
        });

        session()->flash('success', 'Small Group created successfully.');
        $this->redirect(route('small-groups.index'), navigate: true);
    }

    public function render()
    {
        $leaderSearch = trim($this->leaderSearch);
        $roleSearch = str_replace(' ', '_', strtolower($leaderSearch));
        $users = User::query()
            ->visibleTo(auth()->user(), 'users.view')
            ->with('roles')
            ->where('status', User::STATUS_ACTIVE)
            ->when($leaderSearch !== '', fn ($query) => $query->where(function ($query) use ($leaderSearch, $roleSearch): void {
                $query->where('name', 'like', "%{$leaderSearch}%")
                    ->orWhere('email', 'like', "%{$leaderSearch}%")
                    ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', "%{$leaderSearch}%")->orWhere('slug', 'like', "%{$roleSearch}%"));
            }))
            ->orderBy('name')
            ->get();

        return view('livewire.small-group.create-small-group', [
            'users' => $users,
            'statuses' => SmallGroup::STATUSES,
            ...$this->tagViewData(),
        ]);
    }
}
