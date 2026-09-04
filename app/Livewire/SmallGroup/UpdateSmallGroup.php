<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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
            'leader_id' => ['required', 'exists:users,id'],
            'status' => ['required', 'in:' . implode(',', SmallGroup::STATUSES)],
        ];
    }

    public function save(): void
    {
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
            ->orderBy('name')
            ->get();

        return view('livewire.small-group.update-small-group', [
            'users' => $users,
            'statuses' => SmallGroup::STATUSES,
        ]);
    }
}
