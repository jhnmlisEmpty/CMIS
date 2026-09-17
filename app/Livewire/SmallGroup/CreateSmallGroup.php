<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\User;
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
    use WithFileUploads;

    public string $name = '';

    public string $description = '';

    public ?int $leader_id = null;

    public string $status = 'active';

    public $photo;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'leader_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query
                ->where('role', User::ROLE_SMALL_GROUP_LEADER)
                ->where('status', User::STATUS_ACTIVE))],
            'status' => ['required', 'in:'.implode(',', SmallGroup::STATUSES)],
        ];
    }

    public function save(): void
    {
        Gate::authorize('small_groups.create');
        $validated = $this->validate();

        $validated['photo_path'] = $this->photo?->store('small-group-photos', 'public');
        SmallGroup::create($validated);

        session()->flash('success', 'Small Group created successfully.');
        $this->redirect(route('small-groups.index'), navigate: true);
    }

    public function render()
    {
        $users = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->where('role', User::ROLE_SMALL_GROUP_LEADER)
            ->orderBy('name')
            ->get();

        return view('livewire.small-group.create-small-group', [
            'users' => $users,
            'statuses' => SmallGroup::STATUSES,
        ]);
    }
}
