<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\ManagesEventAudience;
use App\Livewire\Concerns\ManagesTags;
use App\Models\Event;
use App\Models\AttendanceSetting;
use App\Models\SmallGroup;
use App\Models\Tag;
use App\Models\Role;
use App\Services\AttendanceAnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Create Event | True Vine World Harvest Church - Pangasinan')]

class CreateEvent extends Component
{
    use ManagesEventAudience, ManagesTags;

    public $title;

    public $description;

    public $event_date;

    public $location;

    public $event_type;

    protected function tagType(): string
    {
        return Tag::TYPE_EVENT;
    }

    public function mount(): void
    {
        $defaults = AttendanceSetting::current();
        $this->attendance_required = $defaults->attendance_required;
        $this->attendance_reward_points = $defaults->reward_points;
        $this->absence_penalty_points = $defaults->penalty_points;
        $this->audience_all_active = $defaults->audience_mode === 'all_active';
        $this->selectedRoles = Role::query()->whereIn('slug', $defaults->role_audience ?? [])->pluck('slug')->all();
        $this->selectedSmallGroups = SmallGroup::query()->active()
            ->whereIn('id', $defaults->small_group_audience ?? [])
            ->pluck('id')->all();
    }

    protected function rules(): array
    {
        return array_merge([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'event_type' => 'required|string|max:255',
        ], $this->audienceRules(), $this->tagRules());
    }

    public function submit(AttendanceAnalyticsService $analytics)
    {
        Gate::authorize('events.create');
        $validated = $this->validate();
        if (! $this->validateAudienceSelection()) {
            return;
        }

        DB::transaction(function () use ($analytics, $validated): void {
            $event = Event::create([
                'title' => $this->title,
                'description' => $this->description,
                'event_date' => $this->event_date,
                'location' => $this->location,
                'event_type' => $this->event_type,
                'attendance_required' => $this->attendance_required,
                'attendance_reward_points' => $this->attendance_reward_points,
                'absence_penalty_points' => $this->absence_penalty_points,
            ]);
            $this->syncTags($event, $validated['tag_ids'], $validated['pending_tags']);
            $this->syncAudienceRules($event);
            if ($event->attendance_required && $event->event_date->isToday()) {
                $analytics->snapshotEvent($event);
            }
        });
        session()->flash('success', 'Event created successfully!');

        return redirect()->route('events.index');
    }

    public function render()
    {
        return view('livewire.attendance.create-event', [
            ...$this->audienceOptions(),
            ...$this->tagViewData(),
        ]);
    }
}
