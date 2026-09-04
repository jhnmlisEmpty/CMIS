<?php
namespace App\Livewire\Attendance;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Event;

#[Layout('components.layouts.app')]
#[Title('Create Event | True Vine World Harvest Church - Pangasinan')]

class CreateEvent extends Component
{
    public $title, $description, $event_date, $location, $event_type;

    protected $rules = [
        'title' => 'required|string',
        'description' => 'nullable|string',
        'event_date' => 'required|date',
        'location' => 'required|string',
        'event_type' => 'required|string',
    ];

    public function submit()
    {
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
