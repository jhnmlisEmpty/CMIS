<?php
namespace App\Livewire\Attendance;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Event;

#[Layout('components.layouts.app')]
#[Title('Edit Event')]
class UpdateEvent extends Component
{
    public Event $event;
    public string $title = '';
    public string $description = '';
    public string $event_date = '';
    public string $location = '';
    public string $event_type = '';

    protected $rules = [
        'title' => 'required|string',
        'description' => 'nullable|string',
        'event_date' => 'required|date',
        'location' => 'required|string',
        'event_type' => 'required|string',
    ];

    public function mount(Event $event): void
    {
        $this->event = $event;
        $this->title = $event->title;
        $this->description = $event->description ?? '';
        $this->event_date = $event->event_date;
        $this->location = $event->location;
        $this->event_type = $event->event_type;
    }

    public function submit(): void
    {
        $this->validate();
        $this->event->update([
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date,
            'location' => $this->location,
            'event_type' => $this->event_type,
        ]);
        session()->flash('success', 'Event updated successfully!');
        $this->redirect(route('events.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.attendance.update-event');
    }
}
