<?php

namespace App\Livewire\Concerns;

use App\Models\Event;
use App\Models\EventAudienceRule;
use App\Models\SmallGroup;
use App\Models\User;

trait ManagesEventAudience
{
    public bool $attendance_required = false;
    public int $attendance_reward_points = 10;
    public int $absence_penalty_points = 10;
    public bool $audience_all_active = false;
    public array $selectedRoles = [];
    public array $selectedSmallGroups = [];
    public array $selectedUsers = [];

    protected function audienceRules(): array
    {
        return [
            'attendance_required' => ['boolean'],
            'attendance_reward_points' => ['required_if:attendance_required,true', 'integer', 'min:0', 'max:100'],
            'absence_penalty_points' => ['required_if:attendance_required,true', 'integer', 'min:0', 'max:100'],
            'audience_all_active' => ['boolean'],
            'selectedRoles' => ['array'],
            'selectedRoles.*' => ['in:'.implode(',', User::ROLES)],
            'selectedSmallGroups' => ['array'],
            'selectedSmallGroups.*' => ['integer', 'exists:small_groups,id'],
            'selectedUsers' => ['array'],
            'selectedUsers.*' => ['integer', 'exists:users,id'],
        ];
    }

    protected function validateAudienceSelection(): bool
    {
        if (! $this->attendance_required) {
            return true;
        }

        if ($this->audience_all_active || $this->selectedRoles || $this->selectedSmallGroups || $this->selectedUsers) {
            return true;
        }

        $this->addError('audience', 'Choose at least one required audience.');

        return false;
    }

    protected function syncAudienceRules(Event $event): void
    {
        $event->audienceRules()->delete();
        if (! $event->attendance_required) {
            return;
        }

        $rules = [];
        if ($this->audience_all_active) {
            $rules[] = ['audience_type' => EventAudienceRule::TYPE_ALL_ACTIVE, 'audience_key' => null];
        } else {
            foreach ($this->selectedRoles as $role) {
                $rules[] = ['audience_type' => EventAudienceRule::TYPE_ROLE, 'audience_key' => $role];
            }
            foreach ($this->selectedSmallGroups as $groupId) {
                $rules[] = ['audience_type' => EventAudienceRule::TYPE_SMALL_GROUP, 'audience_key' => (string) $groupId];
            }
            foreach ($this->selectedUsers as $userId) {
                $rules[] = ['audience_type' => EventAudienceRule::TYPE_USER, 'audience_key' => (string) $userId];
            }
        }
        $event->audienceRules()->createMany($rules);
    }

    protected function loadEventAudience(Event $event): void
    {
        $this->attendance_required = $event->attendance_required;
        $this->attendance_reward_points = $event->attendance_reward_points;
        $this->absence_penalty_points = $event->absence_penalty_points;
        $rules = $event->audienceRules;
        $this->audience_all_active = $rules->contains('audience_type', EventAudienceRule::TYPE_ALL_ACTIVE);
        $this->selectedRoles = $rules->where('audience_type', EventAudienceRule::TYPE_ROLE)->pluck('audience_key')->all();
        $this->selectedSmallGroups = $rules->where('audience_type', EventAudienceRule::TYPE_SMALL_GROUP)->pluck('audience_key')->map(fn ($id) => (int) $id)->all();
        $this->selectedUsers = $rules->where('audience_type', EventAudienceRule::TYPE_USER)->pluck('audience_key')->map(fn ($id) => (int) $id)->all();
    }

    protected function audienceOptions(): array
    {
        return [
            'audienceRoles' => User::ROLES,
            'audienceGroups' => SmallGroup::query()->visibleTo(auth()->user())->active()->orderBy('name')->get(),
            'audienceUsers' => User::query()->visibleTo(auth()->user())->where('status', User::STATUS_ACTIVE)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
