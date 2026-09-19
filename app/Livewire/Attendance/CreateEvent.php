<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\ManagesEventAudience;
use App\Models\Event;
use App\Services\AttendanceAnalyticsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Create Event | True Vine World Harvest Church - Pangasinan')]

class CreateEvent extends Component
{
    use ManagesEventAudience;
    public $title;

    public $description;

    public $event_date;

    public $location;

    public $event_type;

    protected function rules(): array
    {
        return array_merge([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'location' => 'required|string|max:255',
            'event_type' => 'required|string|max:255',
        ], $this->audienceRules());
    }

    public function submit(AttendanceAnalyticsService $analytics)
    {
        Gate::authorize('events.create');
        $this->validate();
        if (! $this->validateAudienceSelection()) {
            return;
        }
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
        $this->syncAudienceRules($event);
        if ($event->attendance_required && $event->event_date->isToday()) {
            $analytics->snapshotEvent($event);
        }
        session()->flash('success', 'Event created successfully!');

        return redirect()->route('events.index');
    }

    public function render()
    {
        return view('livewire.attendance.create-event', $this->audienceOptions());
    }
}
