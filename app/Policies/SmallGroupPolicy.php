<?php

namespace App\Policies;

use App\Models\SmallGroup;
use App\Models\User;
use App\Services\AccessManager;

class SmallGroupPolicy
{
    public function __construct(private readonly AccessManager $access) {}

    public function view(User $actor, SmallGroup $group): bool
    {
        return $this->access->canAccessSmallGroup($actor, $group, 'small_groups.view');
    }

    public function update(User $actor, SmallGroup $group): bool
    {
        return $this->access->canAccessSmallGroup($actor, $group, 'small_groups.update');
    }

    public function delete(User $actor, SmallGroup $group): bool
    {
        return $this->access->canAccessSmallGroup($actor, $group, 'small_groups.delete');
    }

    public function viewMembers(User $actor, SmallGroup $group): bool
    {
        return $this->access->canAccessSmallGroup($actor, $group, 'group_members.view');
    }
}
