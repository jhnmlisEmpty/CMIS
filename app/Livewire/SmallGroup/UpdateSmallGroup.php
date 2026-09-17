<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Edit Small Group | True Vine World Harvest Church - Pangasinan')]
class UpdateSmallGroup extends Component
{
    use WithFileUploads;

    public SmallGroup $smallGroup;

    public string $name = '';

    public string $description = '';

    public ?int $leader_id = null;

    public string $status = 'active';

    public $photo;

    public function mount(SmallGroup $smallGroup): void
    {
        abort_unless(auth()->user()->canAccessSmallGroup($smallGroup), 403);
        $this->smallGroup = $smallGroup;
        $this->name = $smallGroup->name;
        $this->description = $smallGroup->description ?? '';
        $this->leader_id = $smallGroup->leader_id;
        $this->status = $smallGroup->status;
    }

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
        Gate::authorize('small_groups.update');
        abort_unless(auth()->user()->canAccessSmallGroup($this->smallGroup), 403);
        $validated = $this->validate();

        unset($validated['photo']);
        if ($this->photo) {
            if ($this->smallGroup->photo_path) {
                Storage::disk('public')->delete($this->smallGroup->photo_path);
            }
            $validated['photo_path'] = $this->photo->store('small-group-photos', 'public');
        }
        $this->smallGroup->update($validated);

        session()->flash('success', 'Small Group updated successfully.');
        $this->redirect(route('small-groups.index'), navigate: true);
    }

    public function render()
    {
        $users = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->where('role', User::ROLE_SMALL_GROUP_LEADER)
            ->orderBy('name')
            ->get();

        return view('livewire.small-group.update-small-group', [
            'users' => $users,
            'statuses' => SmallGroup::STATUSES,
        ]);
    }
}
