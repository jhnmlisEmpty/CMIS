<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\Tag;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Small Groups | True Vine World Harvest Church - Pangasinan')]
class IndexSmallGroup extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $tagFilter = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'tagFilter' => ['except' => ''],
        'sortBy' => ['except' => 'name'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTagFilter(): void
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
        $this->reset(['search', 'statusFilter', 'tagFilter']);
        $this->resetPage();
    }

    public function deleteSmallGroup(int $id): void
    {
        Gate::authorize('small_groups.delete');
        $smallGroup = SmallGroup::find($id);

        if ($smallGroup) {
            abort_unless(auth()->user()->canAccessSmallGroup($smallGroup, 'small_groups.delete'), 403);
            $smallGroup->delete();
            session()->flash('success', 'Small Group deleted successfully.');
        }
    }

    public function render()
    {
        $smallGroups = SmallGroup::query()
            ->visibleTo(auth()->user(), 'small_groups.view')
            ->with(['leaders', 'members', 'tags'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('leaders', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                        ->orWhereHas('tags', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->tagFilter, fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($this->tagFilter)))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.small-group.index-small-group', [
            'smallGroups' => $smallGroups,
            'tags' => Tag::forType(Tag::TYPE_SMALL_GROUP)->orderBy('name')->get(),
            'statuses' => SmallGroup::STATUSES,
        ]);
    }
}
