<?php

namespace App\Livewire\Attendance;

use App\Models\Event;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Create Event | True Vine World Harvest Church - Pangasinan')]

class CreateEvent extends Component
{
    public $title;

    public $description;

    public $event_date;

    public $location;

    public $event_type;

    protected $rules = [
        'title' => 'required|string',
        'description' => 'nullable|string',
        'event_date' => 'required|date',
        'location' => 'required|string',
        'event_type' => 'required|string',
    ];

    public function submit()
    {
        Gate::authorize('events.create');
        $this->validate();
        Event::create([
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date,
            'location' => $this->location,
            'event_type' => $this->event_type,
        ]);
        session()->flash('success', 'Event created successfully!');

        return redirect()->route('events.index');
    }

    public function render()
    {
        return view('livewire.attendance.create-event');
    }
}
