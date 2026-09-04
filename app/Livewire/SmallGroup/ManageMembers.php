<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Manage Members | True Vine World Harvest Church - Pangasinan')]
class ManageMembers extends Component
{
    public SmallGroup $smallGroup;
    public string $search = '';

    public function mount(SmallGroup $smallGroup): void
    {
        $this->smallGroup = $smallGroup->load(['members.user']);
    }

    public function addMember(int $userId): void
    {
        // Check if user exists
        $user = User::find($userId);
        if (!$user) {
            session()->flash('error', 'User not found.');
            return;
        }

        // Check if user is already a member of ANY small group
        $existingMembership = SmallGroupMember::where('user_id', $userId)->first();

        if ($existingMembership) {
            if ($existingMembership->small_group_id === $this->smallGroup->id) {
                session()->flash('error', 'This user is already a member of this group.');
            } else {
                $groupName = $existingMembership->smallGroup->name ?? 'another group';
                session()->flash('error', "This user is already a member of \"{$groupName}\". A user can only belong to one small group.");
            }
            return;
        }

        SmallGroupMember::create([
            'small_group_id' => $this->smallGroup->id,
            'user_id' => $userId,
            'status' => SmallGroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $this->smallGroup->refresh();
        session()->flash('success', 'Member added successfully.');
    }

    public function removeMember(int $memberId): void
    {
        $member = SmallGroupMember::find($memberId);
        
        if ($member && $member->small_group_id === $this->smallGroup->id) {
            $member->delete();
            $this->smallGroup->refresh();
            session()->flash('success', 'Member removed successfully.');
        }
    }

    public function toggleMemberStatus(int $memberId): void
    {
        $member = SmallGroupMember::find($memberId);
        
        if ($member && $member->small_group_id === $this->smallGroup->id) {
            $member->update([
                'status' => $member->status === SmallGroupMember::STATUS_ACTIVE 
                    ? SmallGroupMember::STATUS_INACTIVE 
                    : SmallGroupMember::STATUS_ACTIVE,
            ]);
            $this->smallGroup->refresh();
        }
    }

    public function render()
    {
        // Get ALL users who are already members of ANY small group
        $usersInAnyGroup = SmallGroupMember::pluck('user_id')->toArray();

        $availableUsers = User::query()
            ->whereNotIn('id', $usersInAnyGroup)
            ->when($this->search, fn($query) => $query->where(function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return view('livewire.small-group.manage-members', [
            'availableUsers' => $availableUsers,
        ]);
    }
}
