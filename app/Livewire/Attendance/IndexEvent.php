

<?php
namespace App\Livewire\Attendance;

use App\Models\Event;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Events | True Vine World Harvest Church - Pangasinan')]

class IndexEvent extends Component
{
    use WithPagination;
    public string $search = '';
    public string $typeFilter = '';
    public string $sortBy = 'event_date';
    public string $sortDirection = 'desc';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'sortBy' => ['except' => 'event_date'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter']);
        $this->resetPage();
    }

    public function deleteEvent(int $eventId): void
    {
        $event = Event::find($eventId);
        if ($event) {
            $event->delete();
            session()->flash('success', 'Event deleted successfully.');
        }
    }

    public function render()
    {
        $events = Event::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                      ->orWhere('location', 'like', "%{$this->search}%");
            })
            ->when($this->typeFilter, fn($query) => $query->where('event_type', $this->typeFilter))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        $types = Event::query()->distinct()->pluck('event_type')->filter()->values();

        return view('livewire.attendance.index-event', [
            'events' => $events,
            'types' => $types,
        ]);
    }
}
