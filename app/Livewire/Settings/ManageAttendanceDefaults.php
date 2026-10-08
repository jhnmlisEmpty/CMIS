<?php

namespace App\Livewire\Settings;

use App\Models\AttendanceSetting;
use App\Models\Role;
use App\Models\SmallGroup;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Attendance Defaults | Settings')]
class ManageAttendanceDefaults extends Component
{
    public bool $attendance_required = false;
    public int $reward_points = 10;
    public int $penalty_points = 10;
    public string $audience_mode = 'selected';
    public array $role_audience = [];
    public array $small_group_audience = [];

    public function mount(): void
    {
        Gate::authorize('access-control.manage');
        $setting = AttendanceSetting::current();
        $this->attendance_required = $setting->attendance_required;
        $this->reward_points = $setting->reward_points;
        $this->penalty_points = $setting->penalty_points;
        $this->audience_mode = $setting->audience_mode;
        $this->role_audience = Role::query()->whereIn('slug', $setting->role_audience ?? [])->pluck('slug')->all();
        $this->small_group_audience = SmallGroup::active()->whereIn('id', $setting->small_group_audience ?? [])->pluck('id')->all();
    }

    public function save(): void
    {
        Gate::authorize('access-control.manage');
        $data = $this->validate([
            'attendance_required' => ['boolean'],
            'reward_points' => ['required', 'integer', 'min:0', 'max:100'],
            'penalty_points' => ['required', 'integer', 'min:0', 'max:100'],
            'audience_mode' => ['required', Rule::in(['all_active', 'selected'])],
            'role_audience' => ['array'],
            'role_audience.*' => ['string', Rule::exists('roles', 'slug')],
            'small_group_audience' => ['array'],
            'small_group_audience.*' => ['integer', Rule::exists('small_groups', 'id')->where('status', SmallGroup::STATUS_ACTIVE)],
        ]);

        if ($this->attendance_required && $this->audience_mode === 'selected' && ! $this->role_audience && ! $this->small_group_audience) {
            $this->addError('audience', 'Choose at least one default role or small group.');
            return;
        }

        AttendanceSetting::current()->update($data);
        session()->flash('success', 'Attendance defaults updated.');
    }

    public function render()
    {
        return view('livewire.settings.manage-attendance-defaults', [
            'roles' => Role::query()->orderByDesc('is_system')->orderBy('id')->get(),
            'smallGroups' => SmallGroup::active()->orderBy('name')->get(),
        ]);
    }
}
