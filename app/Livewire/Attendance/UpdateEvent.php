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
#[Title('Edit Event')]
class UpdateEvent extends Component
{
    use ManagesEventAudience;
    public Event $event;

    public string $title = '';

    public string $description = '';

    public string $event_date = '';

    public string $location = '';

    public string $event_type = '';

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

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->event_date = $event->event_date->format('Y-m-d');
        $this->location = $event->location;
        $this->event_type = $event->event_type;
        $this->loadEventAudience($event->load('audienceRules'));
    }

    public function submit(AttendanceAnalyticsService $analytics): void
    {
        Gate::authorize('events.update');
        $this->validate();
        if (! $this->event->isAudienceLocked() && ! $this->validateAudienceSelection()) {
            return;
        }
        $values = [
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'event_type' => $this->event_type,
        ];
        if (! $this->event->isAudienceLocked()) {
            $values += [
                'event_date' => $this->event_date,
                'attendance_required' => $this->attendance_required,
                'attendance_reward_points' => $this->attendance_reward_points,
                'absence_penalty_points' => $this->absence_penalty_points,
            ];
        }
        $this->event->update($values);
        if (! $this->event->isAudienceLocked()) {
            $this->syncAudienceRules($this->event);
            if ($this->event->attendance_required && $this->event->event_date->isToday()) {
                $analytics->snapshotEvent($this->event);
            }
        }
        session()->flash('success', 'Event updated successfully!');
        $this->redirect(route('events.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.attendance.update-event', $this->audienceOptions());
    }
}
