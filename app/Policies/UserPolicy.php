<?php

namespace App\Policies;

use App\Models\User;
use App\Services\AccessManager;

class UserPolicy
{
    public function __construct(private readonly AccessManager $access) {}

    public function view(User $actor, User $member): bool
    {
        return $actor->is($member) || $this->access->canAccessMember($actor, $member, 'users.view');
    }

    public function update(User $actor, User $member): bool
    {
        return (! $member->isAdmin() || $actor->isAdmin())
            && $this->access->canAccessMember($actor, $member, 'users.update');
    }

    public function delete(User $actor, User $member): bool
    {
        return (! $member->isAdmin() || $actor->isAdmin())
            && $this->access->canAccessMember($actor, $member, 'users.delete');
    }

    /** @param list<int|string> $roleIds */
    public function assignRoles(User $actor, User $member, array $roleIds = []): bool
    {
        return $this->access->canAssignRoles($actor, $roleIds, $member);
    }
}
